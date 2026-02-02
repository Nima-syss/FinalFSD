// Course Management System - Main JavaScript File

// Get base URL from current location
const baseUrl = window.location.origin + '/course_management';

// ============================================
// 1. AUTOCOMPLETE SEARCH FOR COURSES
// ============================================
const keywordInput = document.getElementById('keyword');
const autocompleteResults = document.getElementById('autocomplete-results');

if (keywordInput && autocompleteResults) {
    let searchTimeout;
    
    keywordInput.addEventListener('input', function() {
        const keyword = this.value.trim();
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        if (keyword.length < 2) {
            autocompleteResults.innerHTML = '';
            autocompleteResults.classList.remove('show');
            return;
        }
        
        // Debounce search
        searchTimeout = setTimeout(() => {
            fetch(`${baseUrl}/ajax/search_courses.php?keyword=${encodeURIComponent(keyword)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        displayAutocompleteResults(data);
                    } else {
                        autocompleteResults.innerHTML = '<div class="autocomplete-item">No results found</div>';
                        autocompleteResults.classList.add('show');
                    }
                })
                .catch(error => {
                    console.error('Autocomplete error:', error);
                });
        }, 300);
    });
    
    function displayAutocompleteResults(courses) {
        autocompleteResults.innerHTML = '';
        
        courses.forEach(course => {
            const item = document.createElement('div');
            item.className = 'autocomplete-item';
            item.innerHTML = `
                <strong>${course.course_code}</strong> - ${course.course_name}
                <br><small>${course.category} | ${course.level}</small>
            `;
            
            item.addEventListener('click', function() {
                keywordInput.value = course.course_name;
                autocompleteResults.innerHTML = '';
                autocompleteResults.classList.remove('show');
            });
            
            autocompleteResults.appendChild(item);
        });
        
        autocompleteResults.classList.add('show');
    }
    
    // Close autocomplete when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target !== keywordInput && e.target !== autocompleteResults) {
            autocompleteResults.innerHTML = '';
            autocompleteResults.classList.remove('show');
        }
    });
}

// ============================================
// 2. INSTRUCTOR DETAILS AUTO-FILL
// ============================================
const instructorSelect = document.getElementById('instructor_id');
const instructorDetails = document.getElementById('instructor-details');

if (instructorSelect && instructorDetails) {
    instructorSelect.addEventListener('change', function() {
        const instructorId = this.value;
        
        if (!instructorId) {
            instructorDetails.innerHTML = '';
            instructorDetails.classList.remove('show');
            return;
        }
        
        fetch(`${baseUrl}/ajax/get_instructor.php?id=${instructorId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Instructor error:', data.error);
                    return;
                }
                
                instructorDetails.innerHTML = `
                    <h4>Instructor Details</h4>
                    <p><strong>Name:</strong> ${data.first_name} ${data.last_name}</p>
                    <p><strong>Email:</strong> ${data.email}</p>
                    <p><strong>Phone:</strong> ${data.phone || 'N/A'}</p>
                    <p><strong>Department:</strong> ${data.department || 'N/A'}</p>
                    <p><strong>Specialization:</strong> ${data.specialization || 'N/A'}</p>
                `;
                instructorDetails.classList.add('show');
            })
            .catch(error => {
                console.error('Instructor fetch error:', error);
            });
    });
    
    // Trigger on page load if instructor is already selected
    if (instructorSelect.value) {
        instructorSelect.dispatchEvent(new Event('change'));
    }
}

// ============================================
// 3. LIVE EMAIL VALIDATION
// ============================================
const emailInput = document.getElementById('email');
const emailFeedback = document.getElementById('email-feedback');

if (emailInput && emailFeedback) {
    let emailTimeout;
    
    // Determine type (student or instructor) based on URL
    const type = window.location.pathname.includes('/instructors/') ? 'instructor' : 'student';
    
    // Get exclude ID if editing
    const excludeId = emailInput.dataset.excludeId || '';
    
    emailInput.addEventListener('input', function() {
        const email = this.value.trim();
        
        clearTimeout(emailTimeout);
        
        if (!email || !isValidEmail(email)) {
            emailFeedback.innerHTML = '';
            emailFeedback.className = 'form-feedback';
            return;
        }
        
        emailTimeout = setTimeout(() => {
            let url = `${baseUrl}/ajax/check_email.php?email=${encodeURIComponent(email)}&type=${type}`;
            if (excludeId) {
                url += `&exclude_id=${excludeId}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        emailFeedback.innerHTML = '✗ This email is already registered';
                        emailFeedback.className = 'form-feedback error';
                    } else {
                        emailFeedback.innerHTML = '✓ Email is available';
                        emailFeedback.className = 'form-feedback success';
                    }
                })
                .catch(error => {
                    console.error('Email validation error:', error);
                });
        }, 500);
    });
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

// ============================================
// 4. COURSE CAPACITY CHECK (ENROLLMENT)
// ============================================
const courseSelect = document.getElementById('course_id');
const courseCapacityInfo = document.getElementById('course-capacity-info');

if (courseSelect && courseCapacityInfo) {
    courseSelect.addEventListener('change', function() {
        const courseId = this.value;
        
        if (!courseId) {
            courseCapacityInfo.innerHTML = '';
            courseCapacityInfo.classList.remove('show', 'full');
            return;
        }
        
        fetch(`${baseUrl}/ajax/check_enrollment.php?course_id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Course capacity error:', data.error);
                    return;
                }
                
                const isFull = data.is_full;
                const html = `
                    <h4>${data.course_name}</h4>
                    <p><strong>Enrolled:</strong> ${data.enrolled_students} / ${data.max_students}</p>
                    <p><strong>Available Slots:</strong> ${data.available_slots}</p>
                    ${isFull ? '<p class="text-danger"><strong>⚠ This course is full!</strong></p>' : ''}
                `;
                
                courseCapacityInfo.innerHTML = html;
                courseCapacityInfo.classList.add('show');
                
                if (isFull) {
                    courseCapacityInfo.classList.add('full');
                } else {
                    courseCapacityInfo.classList.remove('full');
                }
            })
            .catch(error => {
                console.error('Course capacity fetch error:', error);
            });
    });
    
    // Trigger on page load if course is already selected
    if (courseSelect.value) {
        courseSelect.dispatchEvent(new Event('change'));
    }
}

// ============================================
// 5. FORM VALIDATION HELPERS
// ============================================

// Auto-hide alerts after 5 seconds
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => {
            alert.remove();
        }, 500);
    }, 5000);
});

// Confirm delete actions
const deleteLinks = document.querySelectorAll('a[onclick*="confirm"]');
deleteLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        const message = this.getAttribute('onclick').match(/'([^']+)'/)[1];
        if (!confirm(message)) {
            e.preventDefault();
        }
    });
});

// Client-side form validation
const forms = document.querySelectorAll('.form');
forms.forEach(form => {
    form.addEventListener('submit', function(e) {
        const requiredInputs = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.style.borderColor = '#e74c3c';
            } else {
                input.style.borderColor = '#ddd';
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill in all required fields');
        }
    });
});

// ============================================
// 6. TABLE ENHANCEMENTS
// ============================================

// Make table rows clickable (optional enhancement)
const tableRows = document.querySelectorAll('.table tbody tr');
tableRows.forEach(row => {
    row.style.cursor = 'pointer';
});

// ============================================
// 7. SMOOTH SCROLL
// ============================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
        }
    });
});

// ============================================
// 8. LOADING INDICATOR
// ============================================
function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'loading-overlay';
    loader.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    `;
    loader.innerHTML = '<div style="color: white; font-size: 1.5rem;">Loading...</div>';
    document.body.appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('loading-overlay');
    if (loader) {
        loader.remove();
    }
}

// Show loading on form submit
forms.forEach(form => {
    form.addEventListener('submit', function() {
        showLoading();
    });
});

console.log('Course Management System - JavaScript loaded successfully');