document.addEventListener('DOMContentLoaded', () => {
    const userTypeSelect = document.getElementById('userType');
    const studentFields = document.getElementById('student-fields');

    if (userTypeSelect && studentFields) {
        userTypeSelect.addEventListener('change', (e) => {
            if (e.target.value === 'Student') {
                studentFields.style.display = 'block';
                const inputs = studentFields.querySelectorAll('input');
                inputs.forEach(input => input.setAttribute('required', 'required'));
            } else {
                studentFields.style.display = 'none';
                const inputs = studentFields.querySelectorAll('input');
                inputs.forEach(input => input.removeAttribute('required'));
            }
        });
        
        userTypeSelect.dispatchEvent(new Event('change'));
    }
});