<?php
// UI only, no data queried here
?>
<div class="h-full bg-white rounded shadow p-4 mb-6">
  <div class="flex justify-between items-center mb-4">
    <h3 class="text-lg font-semibold">Customers Overview</h3>
  <select id="chartFilter" class="p-2 border border-gray-300 rounded text-sm">
  <option value="daily" selected>Daily</option> <!-- NEW -->
  <option value="weekly">Weekly</option>
  <option value="monthly">Monthly</option>
  <option value="yearly">Yearly</option>
</select>
  </div>
  <canvas id="customersChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chart;

function fetchChartData(filter = 'daily') {
  fetch('./functions/customers/get-customers-data.php?filter=' + filter)
    .then(response => response.json())
    .then(data => {
      if (chart) chart.destroy(); // destroy previous chart
      renderChart(data.labels, data.values, filter);
    });
}

function renderChart(labels, values, filterType) {
  const ctx = document.getElementById('customersChart').getContext('2d');
  chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: `Customers (${filterType.charAt(0).toUpperCase() + filterType.slice(1)})`,
        data: values,
        backgroundColor: 'rgba(8, 26, 56, 0.8)',
        borderRadius: 1,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      plugins: {
        tooltip: {
          mode: 'index',
          intersect: false
        },
        legend: {
          display: true,
          labels: {
            font: {
              size: 14
            }
          }
        }
      },
      scales: {
        x: {
          title: {
            display: true,
            text:
      filterType === 'daily' ? 'Date' :
      filterType === 'weekly' ? 'Week' :
      filterType === 'yearly' ? 'Year' :
      'Month'
          }
        },
        y: {
          beginAtZero: true,
          title: {
            display: true,
            text: 'Total Customers'
          },
          ticks: {
            precision: 0
          }
        }
      }
    }
  });
}

// Fetch data on load
document.addEventListener('DOMContentLoaded', () => {
  fetchChartData();

  document.getElementById('chartFilter').addEventListener('change', function () {
    fetchChartData(this.value);
  });
});
</script>
