
/** AWA Web Vitals Monitoring **/
(function() {
    function sendToAnalytics(metric) {
        console.log("Web Vitals:", metric);
        // gtag("event", metric.name, {value: metric.value, event_category: "Web Vitals"});
    }
    
    // Simulate Web Vitals (in production use actual web-vitals library)
    function observeWebVitals() {
        // First Contentful Paint
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            if (entries.length > 0) {
                sendToAnalytics({name: "FCP", value: entries[0].startTime});
            }
        }).observe({entryTypes: ["paint"]});
        
        // Largest Contentful Paint
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            sendToAnalytics({name: "LCP", value: lastEntry.startTime});
        }).observe({entryTypes: ["largest-contentful-paint"]});
        
        // Cumulative Layout Shift
        let clsValue = 0;
        new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
            sendToAnalytics({name: "CLS", value: clsValue});
        }).observe({entryTypes: ["layout-shift"]});
    }
    
    if ("PerformanceObserver" in window) {
        observeWebVitals();
    }
})();
