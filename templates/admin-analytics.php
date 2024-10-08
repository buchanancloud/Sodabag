<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="get">
        <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page']); ?>">
        <select name="campaign_id">
            <option value="">Select a campaign</option>
            <?php foreach ($campaigns as $campaign) : ?>
                <option value="<?php echo esc_attr($campaign->id); ?>" <?php selected($campaign_id, $campaign->id); ?>>
                    <?php echo esc_html($campaign->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>">
        <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>">
        <input type="submit" class="button" value="View Analytics">
    </form>

    <?php if ($campaign_id && isset($analytics)) : ?>
        <h2><?php echo esc_html(get_the_title($campaign_id)); ?> Analytics</h2>
        <div class="sodabag-analytics-summary">
            <div class="sodabag-analytic-item">
                <h3>Total Submissions</h3>
                <p><?php echo esc_html($analytics['total_submissions']); ?></p>
            </div>
            <div class="sodabag-analytic-item">
                <h3>Total Shares</h3>
                <p><?php echo esc_html($analytics['total_shares']); ?></p>
            </div>
            <div class="sodabag-analytic-item">
                <h3>QR Code Scans</h3>
                <p><?php echo esc_html($analytics['qr_scans']); ?></p>
            </div>
        </div>

        <h3>Shares by Platform</h3>
        <table class="wp-list-table widefat fixed striped">
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
    <?php endif; ?>
</div>