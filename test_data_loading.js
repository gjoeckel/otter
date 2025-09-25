/**
 * Test script to manually trigger data loading and check results
 * Run this in the browser console on the reports page
 */

console.log('🧪 Testing Data Loading...');
console.log('==========================');

// Test 1: Manually trigger data loading
async function testDataLoading() {
    console.log('\n📊 Test 1: Manual Data Loading');
    console.log('-------------------------------');
    
    if (window.reportsDataService && window.reportsDataService.currentDateRange) {
        const { start, end } = window.reportsDataService.currentDateRange;
        console.log('Current date range:', start, 'to', end);
        
        try {
            // Manually call fetchAllData
            const data = await window.reportsDataService.fetchAllData(start, end, 'by-tou', false);
            console.log('✅ fetchAllData succeeded');
            console.log('Data keys:', Object.keys(data));
            console.log('Systemwide data:', data.systemwide);
            
            // Check if data has the expected structure
            if (data.systemwide) {
                console.log('Registrations count:', data.systemwide.registrations_count);
                console.log('Enrollments count:', data.systemwide.enrollments_count);
                console.log('Certificates count:', data.systemwide.certificates_count);
            }
            
            // Manually update __lastSummaryData
            window.__lastSummaryData = data;
            console.log('✅ Updated window.__lastSummaryData');
            
            // Test the count functions
            if (window.setSystemwideRegistrationsCell) {
                window.setSystemwideRegistrationsCell(data.systemwide?.registrations_count || 0);
                console.log('✅ Updated registrations cell');
            }
            
            if (window.setSystemwideEnrollmentsCell) {
                window.setSystemwideEnrollmentsCell(data.systemwide?.enrollments_count || 0);
                console.log('✅ Updated enrollments cell');
            }
            
            // Check table values
            const regCell = document.querySelector('#systemwide-data tbody td:nth-child(3)');
            const enrCell = document.querySelector('#systemwide-data tbody td:nth-child(4)');
            console.log('Table registrations:', regCell ? regCell.textContent : 'NOT FOUND');
            console.log('Table enrollments:', enrCell ? enrCell.textContent : 'NOT FOUND');
            
        } catch (error) {
            console.log('❌ fetchAllData failed:', error.message);
        }
    } else {
        console.log('❌ No current date range available');
    }
}

// Test 2: Check if update functions work
async function testUpdateFunctions() {
    console.log('\n📊 Test 2: Update Functions');
    console.log('----------------------------');
    
    if (window.updateSystemwideCountAndLink) {
        try {
            await window.updateSystemwideCountAndLink();
            console.log('✅ updateSystemwideCountAndLink succeeded');
        } catch (error) {
            console.log('❌ updateSystemwideCountAndLink failed:', error.message);
        }
    } else {
        console.log('❌ updateSystemwideCountAndLink not available');
    }
    
    if (window.updateSystemwideEnrollmentsCountAndLink) {
        try {
            window.updateSystemwideEnrollmentsCountAndLink();
            console.log('✅ updateSystemwideEnrollmentsCountAndLink succeeded');
        } catch (error) {
            console.log('❌ updateSystemwideEnrollmentsCountAndLink failed:', error.message);
        }
    } else {
        console.log('❌ updateSystemwideEnrollmentsCountAndLink not available');
    }
}

// Test 3: Check unified table updater
async function testUnifiedUpdater() {
    console.log('\n📊 Test 3: Unified Table Updater');
    console.log('----------------------------------');
    
    if (window.unifiedTableUpdater && window.__lastSummaryData) {
        try {
            window.unifiedTableUpdater.updateAllTables(window.__lastSummaryData);
            console.log('✅ unifiedTableUpdater.updateAllTables succeeded');
        } catch (error) {
            console.log('❌ unifiedTableUpdater.updateAllTables failed:', error.message);
        }
    } else {
        console.log('❌ unifiedTableUpdater or __lastSummaryData not available');
    }
}

// Run all tests
async function runAllTests() {
    await testDataLoading();
    await testUpdateFunctions();
    await testUnifiedUpdater();
    
    console.log('\n🎯 Test Complete');
    console.log('================');
    console.log('Check the results above to identify the issue.');
}

// Run the tests
runAllTests();
