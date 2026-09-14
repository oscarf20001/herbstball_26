(async function renderAgeChart() {
  const ctx = document.getElementById('ageStructur_canvas');
  if (!ctx) return; // Chart-Canvas nicht vorhanden

  const dataFromBackend = await getAgeStructur();
  if (!dataFromBackend || dataFromBackend.length === 0) return;

  // Zerstöre ggf. existierenden Chart
  const existingChart = Chart.getChart(ctx);
  if (existingChart) existingChart.destroy();

  const labels = dataFromBackend.map(entry => entry.age.toString());
  const values = dataFromBackend.map(entry => entry.anzahl);

  const backgroundColors = [
    '#6A0DAD', // 16
    '#FF8C42', // 17
    '#FDB833'  // 18
    ];

  const chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: labels,
      datasets: [{
        label: 'Alter',
        data: values,
        backgroundColor: backgroundColors.slice(0, values.length),
        hoverOffset: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
        padding: 16
      },
      plugins: {
        legend: {
          display: true,
          position: 'left',
          align: 'center'
        },
        tooltip: {
          enabled: true
        }
      }
    }
  });
})();

// Funktion zum Laden der Daten
async function getAgeStructur() {
  const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

  try {
      const response = await fetch('../server/php/html-structure/dashboard/php/getAgeStructur.php');
      if (!response.ok) throw new Error(`HTTP-Fehler: ${response.status}`);
      return await response.json();
  } catch (error) {
      console.error('Fehler beim Laden der Altersstruktur:', error);
      return null;
  }
}