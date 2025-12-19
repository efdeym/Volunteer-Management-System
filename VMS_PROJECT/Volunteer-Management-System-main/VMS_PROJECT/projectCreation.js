const form = document.getElementById('createForm');
const projectsWrap = document.getElementById('projects');
const toast = document.getElementById('toast');
const projectCountEl = document.getElementById('project-count');

const state = {
  projects: [],
  loading: false,
};

function showToast(message, timeout = 3000) {
  if (!toast) {
    alert(message);
    return;
  }
  toast.textContent = message;
  toast.classList.add('show');
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => toast.classList.remove('show'), timeout);
}

function formatDate(value) {
  if (!value) return '—';
  try {
    const date = new Date(value);
    return date.toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  } catch (err) {
    return value;
  }
}

function statusBadge(status) {
  const labelMap = {
    planned: 'Planned',
    active: 'Active',
    completed: 'Completed',
  };
  const label = labelMap[status] || 'Unknown';
  return `<span class="status ${status}">${label}</span>`;
}

function renderProjects() {
  if (!projectsWrap) return;

  if (state.loading) {
    projectsWrap.innerHTML = `
      <div class="project-card loading"></div>
      <div class="project-card loading"></div>
      <div class="project-card loading"></div>
    `;
    if (projectCountEl) {
      projectCountEl.textContent = 'Loading projects...';
    }
    return;
  }

  if (!state.projects.length) {
    projectsWrap.innerHTML = `
      <div class="empty-state">
        <h3>No projects yet</h3>
        <p>Create your first volunteer project to see it listed here.</p>
      </div>`;
    if (projectCountEl) {
      projectCountEl.textContent = '0 projects';
    }
    return;
  }

  if (projectCountEl) {
    projectCountEl.textContent =
      state.projects.length === 1
        ? '1 project'
        : `${state.projects.length} projects`;
  }

  projectsWrap.innerHTML = state.projects
    .map(
      (project) => `
      <article class="project-card">
        <header>
          <div>
            <h3>${project.title}</h3>
            ${statusBadge(project.status)}
          </div>
          <a class="btn small ghost" href="edit_project.php?id=${project.id}">
            Edit
          </a>
        </header>
        <p class="description">${project.description || 'No description provided.'}</p>
        <dl class="meta">
          <div>
            <dt>Start</dt>
            <dd>${formatDate(project.start_date)}</dd>
          </div>
          <div>
            <dt>End</dt>
            <dd>${formatDate(project.end_date)}</dd>
          </div>
        </dl>
      </article>
    `
    )
    .join('');
}

async function fetchProjects() {
  state.loading = true;
  renderProjects();
  try {
    const res = await fetch('api.php?action=list');
    const data = await res.json();
    if (!Array.isArray(data)) {
      throw new Error('Unexpected response');
    }
    state.projects = data;
  } catch (err) {
    console.error(err);
    showToast('Unable to load projects. Please refresh.');
  } finally {
    state.loading = false;
    renderProjects();
  }
}

async function submitProject(body) {
  const res = await fetch('api.php?action=create', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  });
  const data = await res.json();
  if (!data.success) {
    throw new Error(data.error || 'Unable to create project');
  }
  return data;
}

form?.addEventListener('submit', async (event) => {
  event.preventDefault();
  const formData = new FormData(form);
  const payload = Object.fromEntries(formData.entries());

  form.classList.add('is-submitting');
  try {
    await submitProject(payload);
    form.reset();
    showToast('Project created successfully!');
    fetchProjects();
  } catch (err) {
    console.error(err);
    showToast(err.message);
  } finally {
    form.classList.remove('is-submitting');
  }
});

document.addEventListener('DOMContentLoaded', fetchProjects);
