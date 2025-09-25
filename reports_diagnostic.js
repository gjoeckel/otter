/**
 * Reports Systemwide Data Table Diagnostic Code
 * Run this in the browser console on the reports page to diagnose data flow issues
 */

console.log('🔍 Starting Reports Diagnostic...');
console.log('=====================================');

// Diagnostic function to check data flow
async function diagnoseReportsData() {
    console.log('\n📊 STEP 1: Checking Global Variables');
    console.log('-----------------------------------');
    
    // Check if services are initialized
    console.log('window.reportsDataService:', window.reportsDataService ? '✅ EXISTS' : '❌ MISSING');
    console.log('window.unifiedTableUpdater:', window.unifiedTableUpdater ? '✅ EXISTS' : '❌ MISSING');
    
    // Check current date range
    if (window.reportsDataService) {
        console.log('Current date range:', window.reportsDataService.currentDateRange);
        console.log('Current enrollment mode:', window.reportsDataService.currentEnrollmentMode);
        console.log('Current cohort mode:', window.reportsDataService.currentRegistrationsCohortMode);
    }
    
    // Check legacy variables
    console.log('window.__lastStart:', window.__lastStart || 'NOT SET');
    console.log('window.__lastEnd:', window.__lastEnd || 'NOT SET');
    
    console.log('\n📊 STEP 2: Checking Summary Data');
    console.log('--------------------------------');
    
    // Check if __lastSummaryData exists and what it contains
    if (typeof __lastSummaryData !== 'undefined') {
        console.log('__lastSummaryData:', __lastSummaryData ? '✅ EXISTS' : '❌ NULL/UNDEFINED');
        
        if (__lastSummaryData) {
            console.log('Data keys:', Object.keys(__lastSummaryData));
            console.log('Registrations array:', __lastSummaryData.registrations ? `✅ ${__lastSummaryData.registrations.length} rows` : '❌ MISSING');
            console.log('Enrollments array:', __lastSummaryData.enrollments ? `✅ ${__lastSummaryData.enrollments.length} rows` : '❌ MISSING');
            console.log('Submissions array:', __lastSummaryData.submissions ? `✅ ${__lastSummaryData.submissions.length} rows` : '❌ MISSING');
            console.log('CohortModeSubmissions array:', __lastSummaryData.cohortModeSubmissions ? `✅ ${__lastSummaryData.cohortModeSubmissions.length} rows` : '❌ MISSING');
            
            // Show sample data
            if (__lastSummaryData.registrations && __lastSummaryData.registrations.length > 0) {
                console.log('Sample registration:', __lastSummaryData.registrations[0]);
            }
            if (__lastSummaryData.enrollments && __lastSummaryData.enrollments.length > 0) {
                console.log('Sample enrollment:', __lastSummaryData.enrollments[0]);
            }
        }
    } else {
        console.log('__lastSummaryData: ❌ NOT DEFINED');
    }
    
    console.log('\n📊 STEP 3: Checking Systemwide Table DOM');
    console.log('---------------------------------------');
    
    // Check if the table exists and what values it shows
    const table = document.querySelector('#systemwide-data tbody');
    if (table) {
        console.log('Systemwide table tbody: ✅ EXISTS');
        const rows = table.querySelectorAll('tr');
        console.log('Number of rows:', rows.length);
        
        rows.forEach((row, index) => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 4) {
                console.log(`Row ${index}:`, {
                    startDate: cells[0].textContent,
                    endDate: cells[1].textContent,
                    registrations: cells[2].textContent,
                    enrollments: cells[3].textContent,
                    certificates: cells[4] ? cells[4].textContent : 'N/A'
                });
            }
        });
    } else {
        console.log('Systemwide table tbody: ❌ NOT FOUND');
    }
    
    console.log('\n📊 STEP 4: Checking Radio Button States');
    console.log('--------------------------------------');
    
    // Check registrations mode
    const regRadios = document.querySelectorAll('input[name="systemwide-data-display"]');
    const checkedReg = Array.from(regRadios).find(r => r.checked);
    console.log('Registrations mode:', checkedReg ? checkedReg.value : 'NO RADIO CHECKED');
    
    // Check enrollments mode
    const enrRadios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
    const checkedEnr = Array.from(enrRadios).find(r => r.checked);
    console.log('Enrollments mode:', checkedEnr ? checkedEnr.value : 'NO RADIO CHECKED');
    
    console.log('\n📊 STEP 5: Testing API Call');
    console.log('---------------------------');
    
    // Test the API call directly
    if (window.__lastStart && window.__lastEnd) {
        const testUrl = `reports_api.php?start_date=${encodeURIComponent(window.__lastStart)}&end_date=${encodeURIComponent(window.__lastEnd)}&enrollment_mode=by-tou&all_tables=1`;
        console.log('Testing API URL:', testUrl);
        
        try {
            const response = await fetch(testUrl);
            const data = await response.json();
            
            console.log('API Response Status:', response.status);
            console.log('API Response Keys:', Object.keys(data));
            
            if (data.registrations) {
                console.log('API Registrations:', data.registrations.length, 'rows');
            }
            if (data.enrollments) {
                console.log('API Enrollments:', data.enrollments.length, 'rows');
            }
            if (data.systemwide) {
                console.log('API Systemwide data:', data.systemwide);
            }
            
            // Check for errors
            if (data.error) {
                console.log('❌ API Error:', data.error);
            }
            
        } catch (error) {
            console.log('❌ API Call Failed:', error.message);
        }
    } else {
        console.log('❌ Cannot test API - no date range set');
    }
    
    console.log('\n📊 STEP 6: Testing Count Functions');
    console.log('---------------------------------');
    
    // Test the count functions directly
    if (typeof setSystemwideRegistrationsCell === 'function') {
        console.log('setSystemwideRegistrationsCell: ✅ FUNCTION EXISTS');
        
        // Test setting a value
        setSystemwideRegistrationsCell(999);
        const regCell = document.querySelector('#systemwide-data tbody td:nth-child(3)');
        console.log('Test registrations cell value:', regCell ? regCell.textContent : 'CELL NOT FOUND');
    } else {
        console.log('setSystemwideRegistrationsCell: ❌ FUNCTION NOT FOUND');
    }
    
    if (typeof setSystemwideEnrollmentsCell === 'function') {
        console.log('setSystemwideEnrollmentsCell: ✅ FUNCTION EXISTS');
        
        // Test setting a value
        setSystemwideEnrollmentsCell(888);
        const enrCell = document.querySelector('#systemwide-data tbody td:nth-child(4)');
        console.log('Test enrollments cell value:', enrCell ? enrCell.textContent : 'CELL NOT FOUND');
    } else {
        console.log('setSystemwideEnrollmentsCell: ❌ FUNCTION NOT FOUND');
    }
    
    console.log('\n📊 STEP 7: Testing Update Functions');
    console.log('----------------------------------');
    
    // Test the update functions
    if (typeof updateSystemwideCountAndLink === 'function') {
        console.log('updateSystemwideCountAndLink: ✅ FUNCTION EXISTS');
        try {
            await updateSystemwideCountAndLink();
            console.log('✅ updateSystemwideCountAndLink executed successfully');
        } catch (error) {
            console.log('❌ updateSystemwideCountAndLink failed:', error.message);
        }
    } else {
        console.log('updateSystemwideCountAndLink: ❌ FUNCTION NOT FOUND');
    }
    
    if (typeof updateSystemwideEnrollmentsCountAndLink === 'function') {
        console.log('updateSystemwideEnrollmentsCountAndLink: ✅ FUNCTION EXISTS');
        try {
            updateSystemwideEnrollmentsCountAndLink();
            console.log('✅ updateSystemwideEnrollmentsCountAndLink executed successfully');
        } catch (error) {
            console.log('❌ updateSystemwideEnrollmentsCountAndLink failed:', error.message);
        }
    } else {
        console.log('updateSystemwideEnrollmentsCountAndLink: ❌ FUNCTION NOT FOUND');
    }
    
    console.log('\n🎯 DIAGNOSTIC COMPLETE');
    console.log('=======================');
    console.log('Check the output above for any ❌ errors or missing data.');
    console.log('Key things to look for:');
    console.log('1. Is __lastSummaryData populated with registrations/enrollments?');
    console.log('2. Are the count functions working?');
    console.log('3. Is the API returning data?');
    console.log('4. Are the radio buttons in the correct state?');
}

// Run the diagnostic
diagnoseReportsData().catch(error => {
    console.error('❌ Diagnostic failed:', error);
});

// Also provide a quick check function
window.quickCheck = function() {
    console.log('🔍 Quick Check:');
    console.log('__lastSummaryData:', __lastSummaryData ? 'EXISTS' : 'MISSING');
    if (__lastSummaryData) {
        console.log('Registrations:', __lastSummaryData.registrations ? __lastSummaryData.registrations.length : 'MISSING');
        console.log('Enrollments:', __lastSummaryData.enrollments ? __lastSummaryData.enrollments.length : 'MISSING');
    }
    
    const regCell = document.querySelector('#systemwide-data tbody td:nth-child(3)');
    const enrCell = document.querySelector('#systemwide-data tbody td:nth-child(4)');
    console.log('Table - Registrations:', regCell ? regCell.textContent : 'NOT FOUND');
    console.log('Table - Enrollments:', enrCell ? enrCell.textContent : 'NOT FOUND');
};

console.log('\n💡 TIP: You can also run quickCheck() for a quick status check.');
