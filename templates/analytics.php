<?php
// Ensure this file is being included by a parent file
if (!defined('ABSPATH')) exit;

// Ensure $campaigns is set
if (!isset($campaigns)) {
    wp_die('Invalid data');
}
?>

<div class="sodabag-container">
    <h2>Campaign Analytics</h2>

    <form method="get" class="sodabag-form">
        <select name="campaign_id" required>
            <option value="">Select a campaign</option>
            <?php foreach ($campaigns as $camp) : ?>
                <option value="<?php echo esc_attr($camp->id); ?>" <?php selected($campaign_id, $camp->id); ?>>
                    <?php echo esc_html($camp->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
        <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
        <input type="submit" value="View Analytics" class="sodabag-button">
    </form>

    <?php if ($campaign_id && isset($analytics)) : ?>
        <div class="sodabag-analytics">
            <h3><?php echo esc_html($campaigns[$campaign_id]->name); ?> Analytics</h3>
            
            <div class="sodabag-analytics-grid">
                <div class="sodabag-analytics-item">
                    <h4>Total Submissions</h4>
                    <p><?php echo esc_html($analytics['total_submissions']); ?></p>
                </div>
                <div class="sodabag-analytics-item">
                    <h4>Total Shares</h4>
                    <p><?php echo esc_html($analytics['total_shares']); ?></p>
                </div>
                <div class="sodabag-analytics-item">
                    <h4>QR Code Scans</h4>
                    <p><?php echo esc_html($analytics['qr_scans']); ?></p>
                </div>
            </div>

            <div class="sodabag-analytics-chart">
                <h4>Shares by Platform</h4>
                <canvas id="sharesByPlatformChart"></canvas>
            </div>

            <div class="sodabag-analytics-chart">
                <h4>Submissions Over Time</h4>
                <canvas id="submissionsOverTimeChart"></canvas>
            </div>

            <div class="sodabag-analytics-chart">
                <h4>Engagement Rate</h4>
                <canvas id="engagementRateChart"></canvas>
            </div>

            <h4>Shares by Platform</h4>
            <table class="sodabag-analytics-table">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Shares</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['share_by_platform'] as $platform => $data) : ?>
                        <tr>
                            <td><?php echo esc_html($platform); ?></td>
                            <td><?php echo esc_html($data->count); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var ctx = document.getElementById('sharesByPlatformChart').getContext('2d');
            var shareData = <?php echo json_encode($analytics['share_by_platform']); ?>;
            
            var labels = Object.keys(shareData);
            var data = labels.map(function(label) {
                return shareData[label].count;
            });

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Shares by Platform'
                    }
                }
            });

            // Submissions Over Time Chart
            var submissionsCtx = document.getElementById('submissionsOverTimeChart').getContext('2d');
            var submissionsData = <?php echo json_encode($analytics['submissions_over_time']); ?>;

            new Chart(submissionsCtx, {
                type: 'line',
                data: {
                    labels: Object.keys(submissionsData),
                    datasets: [{
                        label: 'Submissions',
                        data: Object.values(submissionsData),
                        borderColor: '#36A2EB',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Submissions Over Time'
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: 'day'
                            }
                        },
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Engagement Rate Chart
            var engagementCtx = document.getElementById('engagementRateChart').getContext('2d');
            var engagementData = <?php echo json_encode($analytics['engagement_rate']); ?>;

            new Chart(engagementCtx, {
                type: 'bar',
                data: {
                    labels: ['Engagement Rate'],
                    datasets: [{
                        label: 'Engagement Rate',
                        data: [engagementData],
                        backgroundColor: '#4BC0C0'
                    }]
                },
                options: {
                    responsive: true,
                    title: {
                        display: true,
                        text: 'Engagement Rate'
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
        </script>
    <?php endif; ?>
</div>
