const API_URL = "apiProject.php";
const DEFAULT_PER_PAGE = 6;

const state = {
    page: 1,
    perPage: DEFAULT_PER_PAGE,
    search: "",
    status: "",
};

const elements = {
    form: document.getElementById("filters"),
    searchInput: document.getElementById("searchInput"),
    statusSelect: document.getElementById("statusSelect"),
    perPageSelect: document.getElementById("perPageSelect"),
    resetBtn: document.getElementById("resetFilters"),
    list: document.getElementById("projectList"),
    listStatus: document.getElementById("listStatus"),
    message: document.getElementById("message"),
    prevBtn: document.getElementById("prevPage"),
    nextBtn: document.getElementById("nextPage"),
    pageDisplay: document.getElementById("pageDisplay"),
};

let statusOptionsLoaded = false;

init();

function init() {
    bindEvents();
    loadProjects();
}

function bindEvents() {
    elements.form.addEventListener("submit", (event) => {
        event.preventDefault();
        state.search = elements.searchInput.value.trim();
        state.status = elements.statusSelect.value;
        state.perPage = parseInt(elements.perPageSelect.value, 10) || DEFAULT_PER_PAGE;
        state.page = 1;
        loadProjects();
    });

    elements.prevBtn.addEventListener("click", () => {
        if (state.page > 1) {
            state.page -= 1;
            loadProjects();
        }
    });

    elements.nextBtn.addEventListener("click", () => {
        state.page += 1;
        loadProjects();
    });

    if (elements.resetBtn) {
        elements.resetBtn.addEventListener("click", handleResetFilters);
    }

    // Delegate apply button clicks
    elements.list.addEventListener('click', (ev) => {
        const btn = ev.target.closest && ev.target.closest('.apply-btn');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        if (!id) return;
        applyToProject(Number(id));
    });
}

function handleResetFilters() {
    elements.searchInput.value = "";
    elements.statusSelect.value = "";
    elements.perPageSelect.value = String(DEFAULT_PER_PAGE);

    state.search = "";
    state.status = "";
    state.perPage = DEFAULT_PER_PAGE;
    state.page = 1;

    loadProjects();
}

async function loadProjects() {
    setLoading(true);
    elements.message.textContent = "";

    const params = new URLSearchParams({
        page: String(state.page),
        per_page: String(state.perPage),
    });

    if (state.status) params.set("status", state.status);
    if (state.search) params.set("search", state.search);

    try {
        const response = await fetch(`${API_URL}?${params.toString()}`, {
            headers: { Accept: "application/json" },
        });

        if (!response.ok) {
            throw new Error("Unable to load projects right now.");
        }

        const payload = await response.json();
        renderProjects(payload.data);
        renderMeta(payload);
        updateStatusOptions(payload.availableStatuses ?? []);

        if ((payload.data ?? []).length === 0) {
            elements.message.textContent = "No projects matched your filters.";
        }
    } catch (error) {
        console.error(error);
        elements.list.innerHTML = "";
        elements.message.textContent = error.message ?? "Unexpected error";
    } finally {
        setLoading(false);
    }
}

function renderProjects(projects) {
    if (!Array.isArray(projects) || projects.length === 0) {
        elements.list.innerHTML = "";
        return;
    }

    const markup = projects
        .map((project) => {
            const tags = Array.isArray(project.tags)
                ? project.tags.map((tag) => `<span class="tag">${tag}</span>`).join("")
                : "";

            return `
                <article class="project-card">
                    <h3>${project.name}</h3>
                    <p class="summary">${project.summary}</p>
                    <span class="badge ${project.status}">${formatStatus(project.status)}</span>
                    <div class="meta-grid">
                        <span><strong>Volunteers</strong>${project.volunteersNeeded ?? "-"}</span>
                        <span><strong>Start</strong>${project.startDate ?? "TBD"}</span>
                        <span><strong>End</strong>${project.endDate ?? "TBD"}</span>
                    </div>
                    <div class="tags">${tags}</div>
                    <div class="project-actions">
                        <button class="nav apply-btn" data-id="${project.id}">Apply</button>
                    </div>
                </article>`;
        })
        .join("");

    elements.list.innerHTML = markup;
}

async function applyToProject(projectId) {
    try {
        const name = window.prompt('Your full name');
        if (!name) { return; }
        const email = window.prompt('Your email address');
        if (!email) { return; }
        const message = window.prompt('Optional message (availability, skills, etc.)') || '';

        elements.message.textContent = 'Submitting application...';

        const resp = await fetch(`${API_URL}?action=apply`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: JSON.stringify({ project_id: projectId, applicant_name: name, applicant_email: email, message }),
        });

        const payload = await resp.json();
        if (!resp.ok || !payload.success) {
            console.error(payload);
            elements.message.textContent = payload.error || 'Failed to submit application';
            return;
        }
        elements.message.textContent = 'Application submitted — thank you!';
        setTimeout(() => { if (elements.message.textContent === 'Application submitted — thank you!') elements.message.textContent = ''; }, 4000);
    } catch (err) {
        console.error(err);
        elements.message.textContent = 'Unexpected error while submitting application.';
    }
}

function renderMeta(payload) {
    const meta = payload.meta ?? {};
    const { total = 0, page = 1, totalPages = 1, hasPreviousPage = false, hasNextPage = false } = meta;
    elements.listStatus.textContent = `Showing page ${page} of ${totalPages} | ${total} project(s) found`;
    elements.pageDisplay.textContent = `Page ${page} of ${totalPages}`;
    elements.prevBtn.disabled = !hasPreviousPage;
    elements.nextBtn.disabled = !hasNextPage;
}

function updateStatusOptions(statuses) {
    if (statusOptionsLoaded || !Array.isArray(statuses)) {
        return;
    }

    const fragment = document.createDocumentFragment();
    statuses.forEach((status) => {
        const option = document.createElement("option");
        option.value = status;
        option.textContent = formatStatus(status);
        fragment.appendChild(option);
    });

    elements.statusSelect.appendChild(fragment);
    statusOptionsLoaded = true;
}

function setLoading(isLoading) {
    if (isLoading) {
        elements.message.textContent = "Loading projects...";
    } else if (elements.message.textContent === "Loading projects...") {
        elements.message.textContent = "";
    }
}

function formatStatus(status) {
    return typeof status === "string" ? status.replace(/_/g, " ") : "";
}
