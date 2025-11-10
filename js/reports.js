function initializeReports(serviceData) {
    if (!serviceData || !serviceData.hasData) {
        console.log('No service data available for report initialization');
        return;
    }
    
    console.log('Initializing reports with data:', serviceData);
    
    var cityLogo = document.getElementById('cityLogo').src;
    var serviceLogoElement = document.getElementById('serviceLogo');
    var serviceLogo = '';
    
    // Handle service logo
    if (serviceLogoElement) {
        serviceLogo = serviceLogoElement.src;
    }
    
    // PDF export customization
    var pdfCustomization = function(doc) {
        doc.content.unshift({
            columns: [
                { image: cityLogo, width: 80, alignment: 'center' },
                { text: 'Service Feedback Sentiment Report', alignment: 'center', fontSize: 16, margin: [0, 20] },
                serviceLogo ? { image: serviceLogo, width: 80, alignment: 'center' } : { text: 'No Image', alignment: 'center', width: 80 }
            ],
            columnGap: 10,
            margin: [0, 0, 0, 20]
        });
        doc.content.splice(1, 0, {
            text: 'Service: ' + serviceData.serviceName + '\nPeriod: ' + serviceData.yearMonth,
            alignment: 'center',
            margin: [0, 0, 0, 10],
            fontSize: 12
        });
    };

    // Initialize DataTable with export buttons
    $('#reportTable').DataTable({
        dom: 'Bfrtip',
        pageLength: 25,
        buttons: [
            {
                extend: 'copy',
                title: 'Service Feedback Sentiment Report',
                messageTop: 'Service: ' + serviceData.serviceName + '\nPeriod: ' + serviceData.yearMonth
            },
            {
                extend: 'csv',
                title: 'Service Feedback Sentiment Report',
                messageTop: 'Service: ' + serviceData.serviceName + '\nPeriod: ' + serviceData.yearMonth
            },
            {
                extend: 'excel',
                title: 'Service Feedback Sentiment Report',
                messageTop: 'Service: ' + serviceData.serviceName + '\nPeriod: ' + serviceData.yearMonth
            },
            {
                extend: 'pdf',
                title: '',
                messageTop: '',
                customize: pdfCustomization
            },
            {
                extend: 'print',
                title: '',
                messageTop: function() {
                    var html = '<div style="width:100%;display:flex;justify-content:center;align-items:center;gap:40px;margin-bottom:10px;">' +
                        '<img src="' + cityLogo + '" style="height:80px;">' +
                        '<span style="font-size:16px;font-weight:bold;margin:0 15px;">Service Feedback Sentiment Report</span>' +
                        (serviceLogo ? 
                            '<img src="' + serviceLogo + '" style="height:80px;">' : 
                            '<div style="height:80px;width:80px;background:#f8f9fa;border:2px dashed #dee2e6;display:flex;align-items:center;justify-content:center;color:#6c757d;flex-direction:column;font-size:10px;"><i class="fas fa-image" style="font-size:18px;margin-bottom:2px;"></i>No Image</div>'
                        ) +
                        '</div>';
                    html += '<div style="text-align:center;margin-bottom:8px;font-size:12px;">Service: ' + serviceData.serviceName + '<br>Period: ' + serviceData.yearMonth + '</div>';
                    html += '<hr style="border:1px solid #000;margin:10px 0;">';
                    return html;
                },
                customize: function (win) {
                    // Remove extra styling and ensure single page
                    $(win.document.body)
                        .css('margin', '5mm')
                        .css('padding', '0')
                        .find('table')
                        .css('font-size', '9pt')
                        .css('width', '100%');
                    
                    // Remove any empty space at the end
                    $(win.document.body).find('div:empty').remove();
                    
                    // Return to ensure proper rendering
                    return win;
                }
            },
            'colvis'
        ]
    });

    // Initialize Chart.js for service sentiment (Pie Chart) - only for screen view
    const ctxPie = document.getElementById('servicePieChart');
    if (ctxPie) {
        const pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Positive', 'Negative', 'Neutral'],
                datasets: [{
                    data: serviceData.totals,
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(220, 53, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(220, 53, 69, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    title: {
                        display: true,
                        text: 'Sentiment Distribution',
                        font: {
                            size: 16,
                            weight: 'bold'
                        }
                    }
                }
            }
        });
    } else {
        console.warn('Chart canvas element not found');
    }
}

// Initialize when document is ready
$(document).ready(function() {
    // Check if service data is available
    if (typeof window.serviceData !== 'undefined') {
        console.log('Service data found, initializing reports...');
        initializeReports(window.serviceData);
    } else {
        console.log('No service data available');
    }
    
    // Add any additional global event handlers here if needed
});