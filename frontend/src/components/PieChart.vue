<template>
  <Pie :data="chartData" :options="chartOptions" />
</template>

<script setup lang="ts">
import { Pie } from 'vue-chartjs';
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend,
  type ChartData,
  type ChartOptions
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend);

interface Props {
  approved: number;
  pending: number;
  rejected: number;
}

const props = defineProps<Props>();

const chartData: ChartData<'pie'> = {
  labels: ['Approved', 'Pending', 'Rejected'],
  datasets: [
    {
      data: [props.approved, props.pending, props.rejected],
      backgroundColor: [
        '#28a745',
        '#ffc107',
        '#dc3545'
      ],
      borderColor: [
        '#ffffff',
        '#ffffff',
        '#ffffff'
      ],
      borderWidth: 2
    }
  ]
};

const chartOptions: ChartOptions<'pie'> = {
  responsive: true,
  maintainAspectRatio: true,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        padding: 15,
        font: {
          size: 13
        }
      }
    },
    tooltip: {
      callbacks: {
        label: function(context) {
          const label = context.label || '';
          const value = context.parsed || 0;
          const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
          const percentage = ((value / total) * 100).toFixed(1);
          return `${label}: ${value} (${percentage}%)`;
        }
      }
    }
  }
};
</script>

<style>
  .pie-chart-container {
  height: 300px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
</style>
