// Chart.js Configuration
Chart.defaults.font.family = "Inter, system-ui, sans-serif";
Chart.defaults.color = "#6B7280";

// Color scheme based on the design system
const colors = {
    primary: "#2563EB", // Blue
    secondary: "#0EA5E9", // Sky Blue
    accent: "#10B981", // Green
    warning: "#F59E0B", // Amber
    error: "#EF4444", // Red
    neutral: "#6B7280", // Gray
    light: "#F3F4F6", // Light Gray
};

// 1. Monthly Requests Comparison Chart (Bar Chart)
const monthlyRequestsCtx = document
    .getElementById("monthlyRequestsChart")
    .getContext("2d");
new Chart(monthlyRequestsCtx, {
    type: "bar",
    data: {
        labels: chartData.months,
        datasets: [
            {
                label: "Leave Requests",
                data: chartData.leaves,
                backgroundColor: colors.primary,
                borderRadius: 6,
            },
            {
                label: "Reimbursement",
                data: chartData.reimbursements,
                backgroundColor: colors.secondary,
                borderRadius: 6,
            },
            {
                label: "Overtime",
                data: chartData.overtimes,
                backgroundColor: colors.accent,
                borderRadius: 6,
            },
            {
                label: "Official Travel",
                data: chartData.officialTravels,
                backgroundColor: colors.warning,
                borderRadius: 6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    padding: 20,
                },
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: "#F3F4F6",
                },
            },
            x: {
                grid: {
                    display: false,
                },
            },
        },
    },
});

// 2. Request Status Distribution (Doughnut Chart)
const statusDistributionCtx = document
    .getElementById("statusDistributionChart")
    .getContext("2d");
new Chart(statusDistributionCtx, {
    type: "doughnut",
    data: {
        labels: ["Approved", "Pending", "Rejected"],
        datasets: [
            {
                data: [chartData.approveds, chartData.pendings, chartData.rejecteds],
                backgroundColor: [colors.accent, colors.warning, colors.error],
                borderWidth: 0,
                cutout: "60%",
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: "bottom",
                labels: {
                    usePointStyle: true,
                    padding: 20,
                },
            },
        },
    },
});

// 3. Reimbursement Trend (Line Chart)
const reimbursementTrendCtx = document
    .getElementById("reimbursementTrendChart")
    .getContext("2d");
new Chart(reimbursementTrendCtx, {
    type: "line",
    data: {
        labels: chartData.months,
        datasets: [
            {
                label: "Amount (IDR)",
                data: chartData.reimbursementsTotal,
                borderColor: colors.secondary,
                backgroundColor: colors.secondary + "20",
                fill: true,
                tension: 0.4,
                pointBackgroundColor: colors.secondary,
                pointBorderColor: "#fff",
                pointBorderWidth: 2,
                pointRadius: 6,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
            },
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: "#F3F4F6",
                },
            },
            x: {
                grid: {
                    display: false,
                },
            },
        },
    },
});

// // 4. Leave Types Breakdown (Pie Chart)
// const leaveTypesCtx = document
//     .getElementById("leaveTypesChart")
//     .getContext("2d");
// new Chart(leaveTypesCtx, {
//     type: "pie",
//     data: {
//         labels: [
//             "Annual Leave",
//             "Sick Leave",
//             "Personal Leave",
//             "Maternity Leave",
//         ],
//         datasets: [
//             {
//                 data: [40, 25, 20, 15],
//                 backgroundColor: [
//                     colors.primary,
//                     colors.accent,
//                     colors.warning,
//                     colors.secondary,
//                 ],
//                 borderWidth: 0,
//             },
//         ],
//     },
//     options: {
//         responsive: true,
//         maintainAspectRatio: false,
//         plugins: {
//             legend: {
//                 position: "bottom",
//                 labels: {
//                     usePointStyle: true,
//                     padding: 20,
//                 },
//             },
//         },
//     },
// });

// // 5. Overtime Hours by Department (Horizontal Bar Chart)
// const overtimeCtx = document.getElementById("overtimeChart").getContext("2d");
// new Chart(overtimeCtx, {
//     type: "bar",
//     data: {
//         labels: ["IT", "Finance", "HR", "Marketing", "Operations"],
//         datasets: [
//             {
//                 label: "Hours",
//                 data: [120, 85, 45, 95, 110],
//                 backgroundColor: colors.accent,
//                 borderRadius: 6,
//             },
//         ],
//     },
//     options: {
//         responsive: true,
//         maintainAspectRatio: false,
//         indexAxis: "y",
//         plugins: {
//             legend: {
//                 display: false,
//             },
//         },
//         scales: {
//             x: {
//                 beginAtZero: true,
//                 grid: {
//                     color: "#F3F4F6",
//                 },
//             },
//             y: {
//                 grid: {
//                     display: false,
//                 },
//             },
//         },
//     },
// });

// Handle window resize
window.addEventListener("resize", function () {
    if (window.innerWidth >= 1024) {
        sidebarOverlay.classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    }
});
