const ctx = document.getElementById('todaySold_canvas');
ctx.width = 250;
ctx.height = 150;

const ticketsToday = await howManyTicketsToday(); // dynamisch setzen – z. B. aus PHP übergeben
const targetTickets = 3;

new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [''],
      datasets: [{
        label: 'Verkäufe heute',
        data: [ticketsToday],
        backgroundColor: ticketsToday >= targetTickets ? '#22e36f' : '#e3224f'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: Math.max(5, targetTickets + 2),
          ticks: {
            stepSize: 1
          }
        }
      },
      layout: {
        padding: 16
      },
      plugins: {
        legend: {
          display: false
        },
        annotation: {
          annotations: {
            targetLine: {
              type: 'line',
              yMin: targetTickets,
              yMax: targetTickets,
              borderColor: 'blue',
              borderWidth: 2,
              label: {
                enabled: true,
                content: 'Ziel: 3 Tickets',
                position: 'end',
                backgroundColor: 'blue',
                color: 'white'
              }
            }
          }
        }
      }
    }
});

async function drawChart() {
  const canvas = document.getElementById('todaySold_canvas');
  canvas.width = 250;
  canvas.height = 150;

  const ctx = canvas.getContext('2d');

  const ticketsToday = await howManyTicketsToday(); // dynamisch setzen
  const targetTickets = 3;

  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [''],
      datasets: [{
        label: 'Verkäufe heute',
        data: [ticketsToday],
        backgroundColor: ticketsToday >= targetTickets ? '#22e36f' : '#e3224f'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      scales: {
        y: {
          beginAtZero: true,
          suggestedMax: Math.max(5, targetTickets + 2),
          ticks: {
            stepSize: 1
          }
        }
      },
      layout: {
        padding: 16
      },
      plugins: {
        legend: {
          display: false
        },
        annotation: {
          annotations: {
            targetLine: {
              type: 'line',
              yMin: targetTickets,
              yMax: targetTickets,
              borderColor: 'blue',
              borderWidth: 2,
              label: {
                enabled: true,
                content: 'Ziel: 3 Tickets',
                position: 'end',
                backgroundColor: 'blue',
                color: 'white'
              }
            }
          }
        }
      }
    }
  });
}

async function howManyTicketsToday() {
  const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

  try {
    const response = await fetch('../server/php/html-structure/dashboard/php/howManyTicketsToday.php');

    if (!response.ok) {
      throw new Error(`HTTP-Fehler: ${response.status}`);
    }

    const data = await response.json();

    return data.count;

  } catch (error) {
    console.error('Fehler beim Abrufen der Anzahl:', error);
    return 0; // besser 0 als null, damit Chart was anzeigen kann
  }
}

// Chart zeichnen beim Laden des Scripts
drawChart();