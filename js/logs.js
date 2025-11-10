$(document).ready(function() {
    // Load logs on page load
    const today = $('#dateFilter').data('today');
    loadLogs(today);

    // Load logs when button is clicked
    $('#loadLogs').click(function() {
        const selectedDate = $('#dateFilter').val();
        loadLogs(selectedDate);
    });

    // Load logs when date is changed
    $('#dateFilter').change(function() {
        const selectedDate = $(this).val();
        loadLogs(selectedDate);
    });

    function loadLogs(date) {
        $('#logsTableBody').html('<tr><td colspan="7" class="text-center py-4">Loading logs...</td></tr>');
        
        $.ajax({
            url: '../connection/fetch_logs.php',
            type: 'POST',
            data: { date: date },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    displayLogs(response.logs);
                } else {
                    $('#logsTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">Error loading logs: ' + response.message + '</td></tr>');
                }
            },
            error: function(xhr, status, error) {
                $('#logsTableBody').html('<tr><td colspan="7" class="text-center py-4 text-danger">Error loading logs. Please try again.</td></tr>');
                console.error('Error:', error);
            }
        });
    }

    function displayLogs(logs) {
        const tbody = $('#logsTableBody');
        tbody.empty();

        if (logs.length > 0) {
            logs.forEach(log => {
                const loginTime = new Date(log.login_time).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

                const logoutTime = log.logout_time ? 
                    new Date(log.logout_time).toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    }) : 'N/A';

                const row = `
                    <tr>
                        <td>${escapeHtml(log.username || 'Unknown')}</td>
                        <td>${escapeHtml(capitalizeFirst(log.user_type))}</td>
                        <td>
                            <span class="status-badge status-${log.login_status}">
                                ${capitalizeFirst(log.login_status)}
                            </span>
                        </td>
                        <td>${escapeHtml(log.ip_address || 'N/A')}</td>
                        <td>${escapeHtml(log.device_info || 'N/A')}</td>
                        <td>${loginTime}</td>
                        <td>${logoutTime}</td>
                    </tr>
                `;
                tbody.append(row);
            });
        } else {
            tbody.html('<tr><td colspan="7" class="text-center py-4">No user logs found for the selected date.</td></tr>');
        }
    }

    function escapeHtml(unsafe) {
        return unsafe
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function capitalizeFirst(string) {
        return string.charAt(0).toUpperCase() + string.slice(1);
    }
});