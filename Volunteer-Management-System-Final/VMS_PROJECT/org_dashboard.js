document.addEventListener('DOMContentLoaded', () => {
    loadProjects();
    loadApplications();

    document.getElementById('createProjectForm').addEventListener('submit', handleCreateProject);
    document.getElementById('editProjectForm').addEventListener('submit', handleUpdateProject);
});

async function loadProjects() {
    try {
        const response = await fetch('api.php?action=get_my_projects&t=' + Date.now());
        const res = await response.json();

        if (res.error && res.error.includes('Access Denied')) {
            window.location.href = 'login.php';
            return;
        }

        const tbody = document.querySelector('#projectsTable tbody');
        tbody.innerHTML = '';

        if (res.success && res.data) {
            res.data.forEach(proj => {
                const projData = JSON.stringify(proj).replace(/"/g, '&quot;');
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><strong>${proj.title}</strong></td>
                    <td>${proj.location || '-'}</td>
                    <td>${proj.start_date}<br><small>${proj.end_date || ''}</small></td>
                    <td>${proj.max_capacity}</td>
                    <td><span class="status-badge">${proj.status}</span></td>
                    <td style="text-align:right;">
                        <button onclick="openEditModal('${projData}')" class="btn secondary">Update</button>
                        <button onclick="deleteProject(${proj.project_id})" class="btn ghost" style="color:red;">Delete</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) { 
        console.error("Project Load Error:", err); 
    }
}

async function handleCreateProject(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await sendRequest('api.php?action=create_project', formData);
    if (res.success) {
        alert("Project created successfully!");
        window.location.reload();
    } else {
        alert("Error: " + res.error);
    }
}

async function handleUpdateProject(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const res = await sendRequest('api.php?action=update_project', formData);
    if (res.success) {
        alert("Project updated successfully!");
        window.location.reload();
    } else {
        alert("Error: " + res.error);
    }
}

async function loadApplications() {
    try {
        const response = await fetch('api.php?action=get_applications');
        const text = await response.text();

        if (!text || text.trim() === "") {
            console.error("Hata: Sunucu boş yanıt döndürdü.");
            return;
        }

        const res = JSON.parse(text);
        console.log("Ekrana basılacak veri:", res);


        const tbody = document.querySelector('#applicationsTable tbody');
        if (!tbody) {
            console.error("Hata: applicationsTable bulunamadı.");
            return;
        } 
        
        tbody.innerHTML = '';

        if (res.success && res.data) {
            if (res.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;">No pending applications.</td></tr>';
                return;
            }

            res.data.forEach(app => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td style="padding:15px;">
                        <div style="font-weight:bold;">${app.first_name} ${app.last_name}</div>
                        <div style="font-size:0.8rem; color:#666;">${app.email}</div>
                    </td>
                    <td><span style="background:#eee; padding:2px 8px; border-radius:4px;">${app.project_title}</span></td>
                    <td>${new Date(app.application_date).toLocaleDateString()}</td>
                    <td style="text-align:right;">
                        <button onclick="decideApp(${app.application_id}, 'Approved')" 
                                style="background:#d1fae5; color:#065f46; border:none; padding:5px 12px; border-radius:6px; cursor:pointer; font-weight:bold; margin-right:5px;">
                                Approve
                        </button>
                        <button onclick="decideApp(${app.application_id}, 'Rejected')" 
                                style="background:#fee2e2; color:#b91c1c; border:none; padding:5px 12px; border-radius:6px; cursor:pointer; font-weight:bold;">
                                Reject
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (err) { 
        console.error("Yükleme Hatası:", err); 
    }
}


window.decideApp = decideApp;
window.deleteProject = deleteProject;

async function decideApp(id, status) {
    if (!confirm(`Are you sure you want to ${status.toLowerCase()} this volunteer?`)) return;
    const formData = new FormData();
    formData.append('app_id', id);
    formData.append('status', status);
    
    const res = await sendRequest('api.php?action=update_application', formData);
    if (res.success) {
        window.location.reload();
    } else {
        alert("Decision Error: " + res.error);
    }
}

async function sendRequest(url, formData) {
    try {
        const response = await fetch(url, { method: 'POST', body: formData });
        return await response.json();
    } catch (err) {
        return { success: false, error: "Network Error" };
    }
}

function openEditModal(projJson) {
    const proj = JSON.parse(projJson);
    document.getElementById('edit_project_id').value = proj.project_id;
    document.getElementById('edit_title').value = proj.title;
    document.getElementById('edit_location').value = proj.location;
    document.getElementById('edit_start_date').value = proj.start_date;
    document.getElementById('edit_end_date').value = proj.end_date || '';
    document.getElementById('edit_capacity').value = proj.max_capacity;
    document.getElementById('edit_status').value = proj.status;
    document.getElementById('edit_description').value = proj.description;
    document.getElementById('editModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('editModal').style.display = 'none';
}