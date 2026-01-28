/**
 * Client-side form validation
 */

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('demandForm');
    
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate zip code
        const zipCode = document.getElementById('zip_code');
        if (zipCode) {
            const zipValue = zipCode.value.trim();
            if (!/^\d{5}$/.test(zipValue)) {
                isValid = false;
                zipCode.setCustomValidity('Zip code must be exactly 5 digits');
            } else {
                zipCode.setCustomValidity('');
            }
        }
        
        // Validate annual kWh
        const annualKwh = document.getElementById('annual_kwh');
        if (annualKwh) {
            const kwhValue = parseFloat(annualKwh.value);
            if (isNaN(kwhValue) || kwhValue <= 0) {
                isValid = false;
                annualKwh.setCustomValidity('Annual kWh must be a positive number');
            } else if (kwhValue < 1000 || kwhValue > 100000) {
                isValid = false;
                annualKwh.setCustomValidity('Annual kWh must be between 1,000 and 100,000');
            } else {
                annualKwh.setCustomValidity('');
            }
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
    
    // Real-time validation feedback
    const zipCode = document.getElementById('zip_code');
    if (zipCode) {
        zipCode.addEventListener('input', function() {
            const value = this.value.trim();
            if (value.length === 5 && /^\d{5}$/.test(value)) {
                this.setCustomValidity('');
            }
        });
    }
    
    const annualKwh = document.getElementById('annual_kwh');
    if (annualKwh) {
        annualKwh.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (!isNaN(value) && value > 0 && value >= 1000 && value <= 100000) {
                this.setCustomValidity('');
            }
        });
    }
});
