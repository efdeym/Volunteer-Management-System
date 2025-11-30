const events = [
  {
    title: 'Community Food Drive',
    date: '2025-12-05',
    description: 'Sort donations, assemble care packages, and distribute them across the city.'
  },
  {
    title: 'STEM Mentorship Night',
    date: '2025-12-12',
    description: 'Pair volunteers with students to build science kits and answer career questions.'
  },
  {
    title: 'Parks Clean-Up Blitz',
    date: '2026-01-08',
    description: 'Join teams across four parks to remove litter and update signage.'
  }
];

const timeline = [
  { title: 'Day 1', detail: 'Install XAMPP, set up repository.' },
  { title: 'Day 2', detail: 'Configure database and seed admin user.' },
  { title: 'Day 3', detail: 'Build signup/login with hashing.' },
  { title: 'Day 4', detail: 'Complete dashboard and admin panel.' },
  { title: 'Day 5', detail: 'QA testing and accessibility review.' },
  { title: 'Day 6', detail: 'Export PDFs, finalize documentation.' }
];

document.addEventListener('DOMContentLoaded', () => {
  const eventGrid = document.querySelector('[data-event-grid]');
  if (eventGrid) {
    eventGrid.innerHTML = events.map(event => `
      <article class="card">
        <h3>${event.title}</h3>
        <p class="card-date">${new Date(event.date).toLocaleDateString()}</p>
        <p>${event.description}</p>
        <a class="btn ghost" href="signup.php">Join via Dashboard</a>
      </article>
    `).join('');
  }

  const timelineEl = document.querySelector('[data-timeline]');
  if (timelineEl) {
    timelineEl.innerHTML = timeline.map(item => `<li><h4>${item.title}</h4><p>${item.detail}</p></li>`).join('');
  }

  const stats = {
    volunteers: 126,
    events: events.length,
    hours: 842
  };
  document.querySelectorAll('[data-stat]').forEach(node => {
    const key = node.getAttribute('data-stat');
    if (stats[key]) {
      node.textContent = stats[key];
    }
  });

  const navToggle = document.querySelector('[data-nav-toggle]');
  const nav = document.querySelector('[data-nav]');
  if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
      nav.classList.toggle('open');
    });
  }
});
