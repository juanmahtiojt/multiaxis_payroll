// Contextual Help System for Multi Axis Payroll
// This script adds help buttons to various pages

// Help content for different pages
const helpContent = {
    dashboard: {
        title: "Dashboard Help",
        content: `
            <h5>Welcome to your Dashboard!</h5>
            <p>This is your main control center. Here's what you can do:</p>
            <ul>
                <li><strong>Employee Counts:</strong> Shows total employees in each category</li>
                <li><strong>Quick Access:</strong> Click on any card to go directly to that section</li>
                <li><strong>Navigation:</strong> Use the sidebar menu to access all features</li>
            </ul>
        `
    },
    employees: {
        title: "Employee Management Help",
        content: `
            <h5>Managing Employees</h5>
            <ul>
                <li><strong>Add Employee:</strong> Click "Add Employee" to add new staff</li>
                <li><strong>Edit:</strong> Click the edit icon to modify employee details</li>
                <li><strong>Delete:</strong> Click the delete icon to remove employees (be careful!)</li>
                <li><strong>Search:</strong> Use the search box to find specific employees</li>
            </ul>
        `
    },
    attendance: {
        title: "Attendance Help",
        content: `
            <h5>Recording Attendance</h5>
            <ul>
                <li><strong>Mark Present/Absent:</strong> Use the checkboxes for each employee</li>
                <li><strong>Overtime:</strong> Enter overtime hours in the OT column</li>
                <li><strong>Late/Absent:</strong> Record any late arrivals or absences</li>
                <li><strong>Save:</strong> Always click "Save Attendance" when done</li>
            </ul>
        `
    },
    payroll: {
        title: "Payroll Processing Help",
        content: `
            <h5>Processing Payroll</h5>
            <ul>
                <li><strong>Select Employee:</strong> Choose an employee to process</li>
                <li><strong>Review Details:</strong> Check all pre-filled information</li>
                <li><strong>Enter Hours:</strong> Add overtime and special hours</li>
                <li><strong>Calculate:</strong> System will auto-calculate totals</li>
                <li><strong>Submit:</strong> Click "Submit Payroll" to finalize</li>
            </ul>
        `
    },
    payslips: {
        title: "Payslip Help",
        content: `
            <h5>Viewing Payslips</h5>
            <ul>
                <li><strong>Select Employee:</strong> Choose whose payslip to view</li>
                <li><strong>Date Range:</strong> Select the pay period</li>
                <li><strong>View:</strong> Click "View Payslip" to see details</li>
                <li><strong>Print:</strong> Use Ctrl+P to print or save as PDF</li>
            </ul>
        `
    }
};

// Function to create help button
function createHelpButton(pageKey) {
    const helpButton = document.createElement('button');
    helpButton.className = 'btn btn-info btn-sm position-fixed';
    helpButton.style.cssText = 'bottom: 20px; right: 20px; z-index: 1000; border-radius: 50%; width: 50px; height: 50px;';
    helpButton.innerHTML = '<i class="fas fa-question"></i>';
    helpButton.title = 'Click for help';
    
    helpButton.addEventListener('click', () => showHelpModal(pageKey));
    
    return helpButton;
}

// Function to show help modal
function showHelpModal(pageKey) {
    const helpData = helpContent[pageKey] || helpContent.dashboard;
    
    const modal = document.createElement('div');
    modal.className = 'modal fade show';
    modal.style.display = 'block';
    modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${helpData.title}</h5>
                    <button type="button" class="close" onclick="closeHelpModal()">&times;</button>
                </div>
                <div class="modal-body">
                    ${helpData.content}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeHelpModal()">Close</button>
                    <a href="help.php" class="btn btn-primary">View Full Help</a>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Function to close help modal
function closeHelpModal() {
    const modal = document.querySelector('.modal');
    if (modal) {
        modal.remove();
    }
}

// Function to determine current page
function getCurrentPage() {
    const path = window.location.pathname;
    const page = path.split('/').pop();
    
    if (page.includes('dashboard')) return 'dashboard';
    if (page.includes('add_user') || page.includes('employee')) return 'employees';
    if (page.includes('attendance')) return 'attendance';
    if (page.includes('payroll')) return 'payroll';
    if (page.includes('payslip')) return 'payslips';
    
    return 'dashboard';
}

// Initialize help system
document.addEventListener('DOMContentLoaded', function() {
    // Don't add help button on help page itself
    if (!window.location.pathname.includes('help.php')) {
        const pageKey = getCurrentPage();
        const helpButton = createHelpButton(pageKey);
        document.body.appendChild(helpButton);
    }
});

// Keyboard shortcut for help (F1)
document.addEventListener('keydown', function(e) {
    if (e.key === 'F1') {
        e.preventDefault();
        const pageKey = getCurrentPage();
        showHelpModal(pageKey);
    }
});

// Add floating help widget
function createFloatingHelpWidget() {
    const widget = document.createElement('div');
    widget.className = 'help-widget';
    widget.innerHTML = `
        <div class="help-widget-content">
            <h6>Need Help?</h6>
            <ul>
                <li><a href="help.php">Full Help Guide</a></li>
                <li><a href="#" onclick="showHelpModal('${getCurrentPage()}')">Quick Help</a></li>
                <li><a href="help.php#contact">Contact Support</a></li>
            </ul>
        </div>
    `;
    
    widget.style.cssText = `
        position: fixed;
        bottom: 80px;
        right: 20px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: none;
        z-index: 1001;
    `;
    
    document.body.appendChild(widget);
    
    // Toggle widget visibility
    const helpButton = document.querySelector('.btn-info.position-fixed');
    if (helpButton) {
        helpButton.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            widget.style.display = widget.style.display === 'none' ? 'block' : 'none';
        });
    }
}

// Initialize floating widget
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(createFloatingHelpWidget, 1000);
});
