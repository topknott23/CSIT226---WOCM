document.addEventListener('DOMContentLoaded', () => {
    const userTypeSelect = document.getElementById('userType');
    const studentFields = document.getElementById('student-fields');

    if (userTypeSelect && studentFields) {
        userTypeSelect.addEventListener('change', (e) => {
            if (e.target.value === 'Student') {
                studentFields.style.display = 'block';
                studentFields.querySelectorAll('input').forEach(i => i.setAttribute('required', 'required'));
            } else {
                studentFields.style.display = 'none';
                studentFields.querySelectorAll('input').forEach(i => i.removeAttribute('required'));
            }
        });
        userTypeSelect.dispatchEvent(new Event('change'));
    }
});