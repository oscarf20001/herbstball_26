const ctx = document.getElementById('webStatisticsAccessLocations_canvas');
ctx.width = 250;
ctx.height = 150;

const backEndData = await getAccessLocations();
const accessData = convertCityArrayToObject(backEndData);
const barColor = '#009FB7'; // cool, kontrastreich

new Chart(ctx, {
  type: 'bar',
  data: {
    labels: Object.keys(accessData), // Ortsnamen als Labels
    datasets: [{
      label: 'Zugriffe',
      data: Object.values(accessData), // Zahlen als Daten
      backgroundColor: barColor
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    layout: {
      padding: 16
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 1
        }
      }
    },
    plugins: {
      legend: {
        display: false
      }
    }
  }
});

async function getAccessLocations(){
  const basePath = window.location.hostname.includes('localhost') ? '/Metis/herbstball_25' : '';

  try {
      const response = await fetch('../server/php/html-structure/dashboard/php/getAccessLocationsData.php');
      
      if (!response.ok) {
        throw new Error(`HTTP-Fehler: ${response.status}`);
      }

      const data = await response.json();
      return data;

  } catch (error) {
      console.error('Fehler beim Abrufen der Access-Location Statistik:', error);
      return null;
  }
}

function convertCityArrayToObject(dataArray) {
  return dataArray.reduce((acc, item) => {
    acc[item.city] = item.anzahl;
    return acc;
  }, {});
}