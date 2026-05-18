document.addEventListener('DOMContentLoaded', () => {
    const studentFields = document.getElementById('student-fields');

    if (studentFields) {
        const inputs = studentFields.querySelectorAll('input');
        inputs.forEach(input => input.setAttribute('required', 'required'));
    }
});