
  // Handle sidebar toggle for mobile
  $(document).ready(function() {
    $('[data-widget="pushmenu"]').click(function(e) {
      e.preventDefault();
      $('body').toggleClass('sidebar-open');
    });
    
    // Close sidebar when clicking on overlay
    $('.sidebar-overlay').click(function() {
      $('body').removeClass('sidebar-open');
    });
    
    // Store the original click handler for nav links
    $('.nav-sidebar .nav-link').each(function() {
      var $link = $(this);
      var originalHref = $link.attr('href');
      
      // Only modify if it's not a logout link
      if (originalHref && !originalHref.includes('logout')) {
        $link.attr('data-href', originalHref);
        $link.removeAttr('href');
        
        $link.click(function(e) {
          e.preventDefault();
          
          // Close the sidebar first
          if ($(window).width() < 992) {
            $('body').removeClass('sidebar-open');
            
            // Wait for sidebar to close before navigating
            setTimeout(function() {
              window.location.href = originalHref;
            }, 300); // Match the sidebar transition time
          } else {
            // For desktop, navigate immediately
            window.location.href = originalHref;
          }
        });
      }
    });
    
    // Handle logout links separately
    $('.nav-sidebar .nav-link[href*="logout"]').click(function() {
      if ($(window).width() < 992) {
        $('body').removeClass('sidebar-open');
      }
      // Allow the default behavior for logout
    });
  });
