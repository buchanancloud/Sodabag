<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post">
        <?php
        settings_fields($this->plugin_name);
        do_settings_sections($this->plugin_name);
        ?>
        <h2>General Settings</h2>
        <!-- General settings fields will be added here by the settings API -->

        <h2>LLM Integration Settings</h2>
        <!-- LLM integration fields will be added here by the settings API -->
		
		<h2>REST API</h2>
        <p>
            <button type="button" class="button button-secondary" id="sodabag-create-rest-endpoint">Create and Verify REST Endpoint</button>
 <button type="button" class="button button-secondary" id="sodabag-populate-custom-url">Populate Custom URL Data</button>
        <h2>Data Management</h2>
        <p>
			<button type="button" class="button button-secondary" id="sodabag-populate-custom-story-url">Populate Custom Story URL</button>
            <button type="button" class="button button-secondary" id="sodabag-delete-data">Delete All Data</button>
            <button id="sodabag-check-llm-columns" class="button button-secondary">Check LLM Database Columns</button>
            <button type="button" class="button button-secondary" id="sodabag-populate-demo">Populate Demo Data</button>
        </p>
        <?php submit_button('Save Settings'); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Delete all data
    $("#sodabag-delete-data").on("click", function() {
        if (confirm("Are you sure you want to delete all plugin data? This action cannot be undone.")) {
            $.post(ajaxurl, {
                action: "sodabag_delete_all_data",
                nonce: sodabag_ajax.nonce
            }, function(response) {
                alert(response.data.message);
                location.reload();
            });
        }
    });

    // Populate demo data
    $("#sodabag-populate-demo").on("click", function() {
        if (confirm("Are you sure you want to populate demo data? This will overwrite any existing data.")) {
            $.post(ajaxurl, {
                action: "sodabag_populate_demo_data",
                nonce: sodabag_ajax.nonce
            }, function(response) {
                alert(response.data.message);
                location.reload();
            });
        }
    });

	$("#sodabag-create-rest-endpoint").on("click", function() {
        $.post(ajaxurl, {
            action: "sodabag_create_rest_endpoint",
            nonce: sodabag_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert("Error: " + response.data.message);
            }
        });
    });
	
    // Check LLM database columns
    $("#sodabag-check-llm-columns").on("click", function() {
        $.post(ajaxurl, {
            action: "sodabag_check_llm_database_columns",
            nonce: sodabag_ajax.nonce
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});
	
</script>