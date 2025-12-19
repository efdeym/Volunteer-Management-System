// Organizational Dashboard JavaScript (client -> PHP API)

let employees = [];
let departments = [];

const apiBase = './api.php';
const authBase = './auth.php';

async function fetchJson(url, opts = {}) {
    opts.credentials = 'include';
    if (!opts.headers) opts.headers = {};
    try {
        const res = await fetch(url, opts);
        const data = await res.json();
        if (!res.ok) throw data;
        return data;
    } catch (err) {
        throw err;
    }
}

// Initialize dashboard on page load
document.addEventListener('DOMContentLoaded', async function () {
    // Attach form handlers
    document.getElementById('employeeForm').addEventListener('submit', addEmployee);
    document.getElementById('departmentForm').addEventListener('submit', addDepartment);
    document.getElementById('loginForm').addEventListener('submit', login);
    document.getElementById('searchBox').addEventListener('input', searchEmployees);

    await checkAuthAndLoad();
});

async function checkAuthAndLoad() {
    try {
        const status = await fetchJson(authBase + '?action=status');
        if (status.logged_in) {
            document.getElementById('userLabel').textContent = status.username || '';
            document.getElementById('loginBtn').style.display = 'none';
            document.getElementById('logoutBtn').style.display = 'inline-block';
            await fetchDashboard();
        } else {
            // Not logged in: show login button and clear UI
            document.getElementById('userLabel').textContent = '';
            document.getElementById('loginBtn').style.display = 'inline-block';
            document.getElementById('logoutBtn').style.display = 'none';
            // Clear lists
            employees = [];
            departments = [];
            updateDashboard();
        }
    } catch (err) {
        console.error('Auth check failed', err);
    }
}

async function fetchDashboard() {
    try {
        const data = await fetchJson(apiBase + '?action=get_dashboard');
        departments = data.departments || [];
        employees = data.employees || [];
        populateDepartmentSelect();
        updateDashboard();
    } catch (err) {
        console.error('Failed to load dashboard', err);
        if (err && err.error === 'Not authenticated') {
            openLoginModal();
        }
    }
}

// Update dashboard display
function updateDashboard() {
    updateStats();
    renderEmployeesList();
    renderDepartmentsList();
    renderRecentEmployees();
    renderDepartmentDistribution();
    renderOrgChart();
}

// Update statistics
function updateStats() {
    document.getElementById('totalEmployees').textContent = employees.length;
    document.getElementById('totalDepts').textContent = departments.length;

    const avgTeamSize = departments.length > 0 ? (employees.length / departments.length).toFixed(1) : 0;
    document.getElementById('avgTeamSize').textContent = avgTeamSize;

    const maxCapacity = departments.length * 10;
    const occupancy = maxCapacity > 0 ? Math.round((employees.length / maxCapacity) * 100) : 0;
    document.getElementById('occupancyRate').textContent = occupancy + '%';
}

// Render employees list
function renderEmployeesList() {
    const list = document.getElementById('employeesList');
    list.innerHTML = '';

    if (employees.length === 0) {
        list.innerHTML = '<p style="text-align: center; color: #999;">No employees found</p>';
        return;
    }

    employees.forEach(emp => {
        const deptColor = getDepartmentColor(emp.department);
        const item = document.createElement('div');
        item.className = 'employee-item';
        item.innerHTML = `
            <div class="employee-avatar" style="background: linear-gradient(135deg, ${deptColor}, rgba(0,0,0,0.1))">${emp.avatar}</div>
            <div class="employee-info" style="flex: 1; text-align: left;">
                <h4>${emp.name}</h4>
                <p>${emp.position} | ${emp.department}</p>
                <p style="font-size: 11px; color: #bbb;">${emp.email} | ${emp.phone}</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-small btn-edit" onclick="editEmployee(${emp.id})">Edit</button>
                <button class="btn btn-small btn-danger" onclick="deleteEmployee(${emp.id})">Delete</button>
            </div>
        `;
        list.appendChild(item);
    });
}

// Render recent employees
function renderRecentEmployees() {
    const list = document.getElementById('recentEmployeesList');
    list.innerHTML = '';

    const recent = employees.slice(-5).reverse();

    if (recent.length === 0) {
        list.innerHTML = '<p style="text-align: center; color: #999;">No employees yet</p>';
        return;
    }

    recent.forEach(emp => {
        const deptColor = getDepartmentColor(emp.department);
        const item = document.createElement('div');
        item.className = 'employee-item';
        item.innerHTML = `
            <div class="employee-avatar" style="background: linear-gradient(135deg, ${deptColor}, rgba(0,0,0,0.1))">${emp.avatar}</div>
            <div class="employee-info" style="flex: 1; text-align: left;">
                <h4>${emp.name}</h4>
                <p>${emp.position}</p>
            </div>
        `;
        list.appendChild(item);
    });
}

// Render departments list
function renderDepartmentsList() {
    const list = document.getElementById('departmentsList');
    list.innerHTML = '';

    if (departments.length === 0) {
        list.innerHTML = '<li style="text-align: center; color: #999;">No departments found</li>';
        return;
    }

    departments.forEach(dept => {
        const empCount = employees.filter(e => e.department === dept.name).length;
        const item = document.createElement('li');
        item.className = 'department-item';
        item.innerHTML = `
            <div>
                <div class="dept-name">ğŸ“ ${dept.name}</div>
                <p style="color: #999; font-size: 12px; margin-top: 5px;">Head: ${dept.head}</p>
                <p style="color: #999; font-size: 12px;">Budget: $${dept.budget.toLocaleString()}</p>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="dept-count">${empCount} members</div>
                <button class="btn btn-small btn-edit" onclick="editDept(${dept.id})">Edit</button>
                <button class="btn btn-small btn-danger" onclick="deleteDept(${dept.id})">Delete</button>
            </div>
        `;
        list.appendChild(item);
    });
}

// Render department distribution
function renderDepartmentDistribution() {
    const container = document.getElementById('deptDistribution');
    container.innerHTML = '';

    departments.forEach(dept => {
        const empCount = employees.filter(e => e.department === dept.name).length;
        const capacity = 10;
        const percentage = (empCount / capacity) * 100;

        const item = document.createElement('div');
        item.className = 'chart-item';
        item.innerHTML = `
            <h4>${dept.name}</h4>
            <p>${empCount}/${capacity} members</p>
            <div class="progress-bar">
                <div class="progress-fill" style="width: ${Math.min(percentage, 100)}%"></div>
            </div>
        `;
        container.appendChild(item);
    });
}

// Render org chart
function renderOrgChart() {
    const container = document.getElementById('orgChartContainer');
    container.innerHTML = '';

    if (departments.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #999;">No organization structure available</p>';
        return;
    }

    departments.forEach((dept, index) => {
        const box = document.createElement('div');
        box.className = 'chart-box';
        box.innerHTML = `
            <h3>${dept.name}</h3>
            <p>${dept.head}</p>
        `;
        container.appendChild(box);
    });
}

// Get department color
function getDepartmentColor(deptName) {
    const colors = {
        'Engineering': '#667eea',
        'HR': '#10b981',
        'Sales': '#f59e0b',
        'Marketing': '#ef4444',
        'Finance': '#8b5cf6',
        'Operations': '#06b6d4',
    };
    return colors[deptName] || '#667eea';
}

// Add employee
function addEmployee(e) {
    e.preventDefault();
    const name = document.getElementById('empName').value.trim();
    const email = document.getElementById('empEmail').value.trim();
    const dept = document.getElementById('empDept').value;
    const position = document.getElementById('empPosition').value.trim();
    const phone = document.getElementById('empPhone').value.trim();

    if (!name || !email || !dept || !position) {
        alert('Please fill in all required fields');
        return;
    }

    const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

    const payload = { name, email, department: dept, position, phone: phone || 'N/A', avatar: initials };
    fetchJson(apiBase + '?action=add_employee', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(res => {
        closeAddEmployeeModal();
        document.getElementById('employeeForm').reset();
        fetchDashboard();
        showNotification('Employee added successfully!');
    }).catch(err => {
        console.error(err);
        alert('Failed to add employee. Make sure you are logged in.');
    });
}

// Add department
function addDepartment(e) {
    e.preventDefault();
    const name = document.getElementById('deptName').value.trim();
    const head = document.getElementById('deptHead').value.trim();
    const budget = parseInt(document.getElementById('deptBudget').value);
    const desc = document.getElementById('deptDesc').value.trim();

    if (!name || !head || !budget || !desc) {
        alert('Please fill in all fields');
        return;
    }

    const payload = { name, head, budget, description: desc };
    fetchJson(apiBase + '?action=add_department', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).then(res => {
        closeAddDeptModal();
        document.getElementById('departmentForm').reset();
        fetchDashboard();
        showNotification('Department added successfully!');
    }).catch(err => {
        console.error(err);
        alert('Failed to add department. Make sure you are logged in.');
    });
}

// Delete employee
function deleteEmployee(id) {
    if (confirm('Are you sure you want to delete this employee?')) {
        fetchJson(apiBase + '?action=delete_employee&id=' + encodeURIComponent(id), { method: 'POST' })
            .then(res => {
                fetchDashboard();
                showNotification('Employee deleted successfully!');
            }).catch(err => {
                console.error(err);
                alert('Failed to delete employee');
            });
    }
}

// Delete department
function deleteDept(id) {
    if (confirm('Are you sure you want to delete this department?')) {
        fetchJson(apiBase + '?action=delete_department&id=' + encodeURIComponent(id), { method: 'POST' })
            .then(res => {
                if (res.success) {
                    fetchDashboard();
                    showNotification('Department deleted successfully!');
                } else {
                    alert(res.error || 'Cannot delete department');
                }
            }).catch(err => {
                console.error(err);
                alert('Failed to delete department');
            });
    }
}

// Edit employee (placeholder)
function editEmployee(id) {
    alert('Edit functionality coming soon!');
}

// Edit department (placeholder)
function editDept(id) {
    alert('Edit functionality coming soon!');
}

// Populate department select
function populateDepartmentSelect() {
    const select = document.getElementById('empDept');
    const currentValue = select.value;
    select.innerHTML = '<option value="">Select Department</option>';

    departments.forEach(dept => {
        const option = document.createElement('option');
        option.value = dept.name;
        option.textContent = dept.name;
        select.appendChild(option);
    });

    if (currentValue) {
        select.value = currentValue;
    }
}

// --- Authentication UI ---
function openLoginModal() { document.getElementById('loginModal').style.display = 'block'; }
function closeLoginModal() { document.getElementById('loginModal').style.display = 'none'; }

async function login(e) {
    e.preventDefault();
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    try {
        const res = await fetchJson(authBase + '?action=login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });
        document.getElementById('userLabel').textContent = res.username || '';
        document.getElementById('loginBtn').style.display = 'none';
        document.getElementById('logoutBtn').style.display = 'inline-block';
        closeLoginModal();
        fetchDashboard();
        showNotification('Signed in');
    } catch (err) {
        console.error('Login failed', err);
        alert('Login failed: ' + (err.error || 'Invalid credentials'));
    }
}

async function logout() {
    try {
        await fetchJson(authBase + '?action=logout', { method: 'POST' });
    } catch (e) {
        // ignore
    }
    document.getElementById('userLabel').textContent = '';
    document.getElementById('loginBtn').style.display = 'inline-block';
    document.getElementById('logoutBtn').style.display = 'none';
    employees = [];
    departments = [];
    updateDashboard();
    showNotification('Signed out');
}

// Search employees
function searchEmployees(e) {
    const query = e.target.value.toLowerCase();
    const filtered = employees.filter(emp =>
        emp.name.toLowerCase().includes(query) ||
        emp.email.toLowerCase().includes(query) ||
        emp.position.toLowerCase().includes(query) ||
        emp.department.toLowerCase().includes(query)
    );

    const list = document.getElementById('employeesList');
    list.innerHTML = '';

    if (filtered.length === 0) {
        list.innerHTML = '<p style="text-align: center; color: #999;">No employees found</p>';
        return;
    }

    filtered.forEach(emp => {
        const deptColor = getDepartmentColor(emp.department);
        const item = document.createElement('div');
        item.className = 'employee-item';
        item.innerHTML = `
            <div class="employee-avatar" style="background: linear-gradient(135deg, ${deptColor}, rgba(0,0,0,0.1))">${emp.avatar}</div>
            <div class="employee-info" style="flex: 1; text-align: left;">
                <h4>${emp.name}</h4>
                <p>${emp.position} | ${emp.department}</p>
                <p style="font-size: 11px; color: #bbb;">${emp.email} | ${emp.phone}</p>
            </div>
            <div class="action-buttons">
                <button class="btn btn-small btn-edit" onclick="editEmployee(${emp.id})">Edit</button>
                <button class="btn btn-small btn-danger" onclick="deleteEmployee(${emp.id})">Delete</button>
            </div>
        `;
        list.appendChild(item);
    });
}

// Switch tabs
function switchTab(e, tabName) {
    // Hide all tabs
    const tabs = document.querySelectorAll('.tab-content');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Remove active class from all buttons
    const buttons = document.querySelectorAll('.tab-btn');
    buttons.forEach(btn => btn.classList.remove('active'));

    // Show selected tab and mark button as active
    document.getElementById(tabName).classList.add('active');
    e.target.classList.add('active');
}

// Modal functions
function openAddEmployeeModal() {
    document.getElementById('addEmployeeModal').style.display = 'block';
}

function closeAddEmployeeModal() {
    document.getElementById('addEmployeeModal').style.display = 'none';
}

function openAddDeptModal() {
    document.getElementById('addDeptModal').style.display = 'block';
}

function closeAddDeptModal() {
    document.getElementById('addDeptModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function (event) {
    const employeeModal = document.getElementById('addEmployeeModal');
    const deptModal = document.getElementById('addDeptModal');

    if (event.target === employeeModal) {
        closeAddEmployeeModal();
    }
    if (event.target === deptModal) {
        closeAddDeptModal();
    }
}

// Show notification
function showNotification(message) {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 15px 25px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        z-index: 2000;
        animation: slideInRight 0.3s ease;
        font-weight: 600;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Add animation styles
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
