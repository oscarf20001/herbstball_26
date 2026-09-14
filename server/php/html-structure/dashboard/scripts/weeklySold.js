const ctx = document.getElementById('weeklySold_canvas').getContext('2d');

const dataFromBackend = await howManyTicketsWeeklySold();

const chartData = {
    labels: createLabels(dataFromBackend),
    datasets: [{
      label: 'Tickets',
      data: extractData(dataFromBackend),
      borderColor: 'rgba(75, 192, 192, 1)',
      backgroundColor: 'rgba(75, 192, 192, 0.2)',
      fill: true,
      tension: 0.3, // für sanfte Linien (optional)
      pointRadius: 5
    }]
};

  const config = {
    type: 'line',
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: {
            padding: 16
        },
      scales: {
        y: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          display: false,
          position: 'top'
        }
      }
    }
  };

const myChart = new Chart(ctx, config);

async function howManyTicketsWeeklySold(){
  const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

  try {
      const response = await fetch('../server/php/html-structure/dashboard/php/weeklySoldTickets.php');
      
      if (!response.ok) {
        throw new Error(`HTTP-Fehler: ${response.status}`);
      }

      const data = await response.json();

      // Wenn dein PHP-Code z. B. so etwas zurückgibt: echo json_encode(['count' => $count]);
      return data;

  } catch (error) {
      console.error('Fehler beim Abrufen der Wochenstatistik:', error);
      return null;
  }
}

function createLabels(data) {
  const labels = [];
  for (let i = 0; i < data.length; i++) {
    if (i === data.length - 1) {
      labels.push('today');
    } else {
      labels.push((data.length - 1 - i) + 'da');
    }
  }
  return labels;
}


function extractData(data) {
  return data.map(entry => entry.anzahl);
}