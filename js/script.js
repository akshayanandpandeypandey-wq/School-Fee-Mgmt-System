/**
 * Greenwood School Fee Core JavaScript
 * =====================================
 * Controls client side dynamic visual events, theme toggles,
 * CSV spreadsheet exporters, side navigation draw, and alert dismissals.
 */

document.addEventListener("DOMContentLoaded", function() {
    console.log("Greenwood Fee Core script initialized.");

    // Initialize all components
    initThemeManager();
    initSidebarControls();
    initAlertDismissers();
});

/**
 * 1. Dark & Light Theme State Manager
 */
function initThemeManager() {
    const htmlEl = document.documentElement;
    const themeBtn = document.getElementById("theme-toggle-btn");
    
    if (!themeBtn) return;
    
    // Check local storage or fallback to page default
    let activeTheme = localStorage.getItem("fee-core-theme");
    
    if (!activeTheme) {
        activeTheme = htmlEl.getAttribute("data-theme") || "dark";
    }
    
    // Apply current theme settings
    applyTheme(activeTheme);
    
    // Listen for toggle triggers
    themeBtn.addEventListener("click", function() {
        const currentTheme = htmlEl.getAttribute("data-theme");
        const nextTheme = (currentTheme === "dark") ? "light" : "dark";
        applyTheme(nextTheme);
    });
}

function applyTheme(theme) {
    const htmlEl = document.documentElement;
    const themeBtn = document.getElementById("theme-toggle-btn");
    
    htmlEl.setAttribute("data-theme", theme);
    localStorage.setItem("fee-core-theme", theme);
    
    // Update theme button icon
    if (themeBtn) {
        const icon = themeBtn.querySelector("i");
        if (icon) {
            if (theme === "dark") {
                icon.className = "fa-solid fa-sun";
                themeBtn.setAttribute("title", "Switch to Light Mode");
            } else {
                icon.className = "fa-solid fa-moon";
                themeBtn.setAttribute("title", "Switch to Dark Mode");
            }
        }
    }
}

/**
 * 2. Mobile Responsive Sidebar Draw Layout
 */
function initSidebarControls() {
    const toggleBtn = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    
    if (!toggleBtn || !sidebar) return;
    
    toggleBtn.addEventListener("click", function(e) {
        e.stopPropagation();
        sidebar.classList.toggle("active");
    });
    
    // Clicking main panel closes drawer if open
    document.addEventListener("click", function(e) {
        if (sidebar.classList.contains("active") && !sidebar.contains(e.target) && e.target !== toggleBtn) {
            sidebar.classList.remove("active");
        }
    });
}

/**
 * 3. Flash alert notifications dismissals
 */
function initAlertDismissers() {
    const alerts = document.querySelectorAll(".alert");
    
    alerts.forEach(alert => {
        // Auto fadeout after 5 seconds
        setTimeout(() => {
            alert.style.transition = "opacity 0.5s ease";
            alert.style.opacity = "0";
            setTimeout(() => alert.remove(), 500);
        }, 5000);
        
        // Manual close click
        const closeBtn = alert.querySelector(".alert-close-btn");
        if (closeBtn) {
            closeBtn.addEventListener("click", function() {
                alert.remove();
            });
        }
    });
}

/**
 * 4. Export HTML tables to CSV files
 * @param {string} filename - Outbound spreadsheet name
 */
function exportTableToCSV(filename = "fee_export.csv") {
    const table = document.querySelector(".table-modern");
    if (!table) {
        alert("⚠️ No data tables found to export!");
        return;
    }
    
    let csv = [];
    const rows = table.querySelectorAll("tr");
    
    rows.forEach(row => {
        // Ignore table footers or action rows
        if (row.classList.contains("table-footer-row") || row.classList.contains("no-print")) return;
        
        let rowData = [];
        const cells = row.querySelectorAll("td, th");
        
        cells.forEach((cell, idx) => {
            // Ignore Actions/Button column
            if (idx === cells.length - 1 && cell.querySelector(".action-buttons-group")) return;
            
            // Clean text contents
            let text = cell.textContent.trim();
            // Clean custom avatars or symbols
            text = text.replace(/^([A-Z])\n/, ""); // Remove student initials avatar prefix
            text = text.replace(/₹/g, "").replace(/\$/g, "").replace(/€/g, "").replace(/£/g, ""); // Clean currency symbols
            text = text.replace(/"/g, '""'); // Escape double quotes
            
            rowData.push(`"${text}"`);
        });
        
        if (rowData.length > 0) {
            csv.push(rowData.join(","));
        }
    });
    
    // Compile and download Blob
    const csvContent = "\uFEFF" + csv.join("\n"); // Add BOM for Excel UTF-8 display compatibility
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    const url = URL.createObjectURL(blob);
    
    link.setAttribute("href", url);
    link.setAttribute("download", filename);
    link.style.visibility = "hidden";
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    console.log("CSV exported successfully: " + filename);
}
