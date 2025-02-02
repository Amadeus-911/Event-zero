    let eventsTable;
    let userData = null;

    document.addEventListener('DOMContentLoaded', function() {
        // Check authentication
        const token = localStorage.getItem('auth_token');
        if (!token) {
            window.location.href = 'login.html';
            return;
        }

        userData = JSON.parse(localStorage.getItem('user'));
        if (!userData) {
            logout();
            return;
        }

        const navbarAuth = document.getElementById('navbarAuth');
        navbarAuth.innerHTML = `
            <span class="text-light me-3">Welcome, ${userData.full_name}</span>
            <button onclick="logout()" class="btn btn-outline-light">Logout</button>
        `;

        if (userData.is_admin) {
            document.getElementById('adminLinks').innerHTML = `
                <div class="nav-divider my-3"></div>
                <h6 class="sidebar-heading px-3 mt-4 mb-1 text-muted">
                    Admin Panel
                </h6>
                <a href="admin/messages.html" class="nav-link">
                    <i class="fas fa-envelope me-2"></i>Contact Messages
                </a>
            `;
        }

        initializeDataTable();

    });

    function initializeDataTable() {
        eventsTable = $('#eventsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: `${ACTIVE_CONFIG.BASE_URL}/api/endpoints/events.php`,
                type: 'GET',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                data: function(d) {
                    // Add custom filters to the request
                    return {
                        ...d,
                        filter: document.querySelector('.nav-link.active').dataset.filter || 'all',
                        dateFilter: $('#dateFilter').val(),
                        statusFilter: $('#statusFilter').val(),
                        page: (d.start / d.length) + 1,
                        limit: d.length
                    };
                }
            },
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            order: [[2, 'asc']], 
            columns: [
                { 
                    data: null,
                    render: function(data) {
                        return `<strong>${data.title}</strong><br>
                                <small class="text-muted">${data.description.substring(0, 50)}...</small>`;
                    }
                },
                { data: 'organizer' },
                { 
                    data: 'event_date',
                    render: function(data) {
                        return new Date(data).toLocaleDateString();
                    }
                },
                { data: 'event_time' },
                { data: 'venue' },
                { 
                    data: null,
                    render: function(data) {
                        return `${data.registered_attendees}/${data.capacity}`;
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        return getStatusBadge(data);
                    }
                },
                { 
                    data: null,
                    render: function(data) {
                        return getActionButtons(data);
                    }
                }
            ]
        });
    
        // Add event listeners for custom filters
        $('#dateFilter').on('change', function() {
            eventsTable.ajax.reload();
        });
    
        $('#statusFilter').on('change', function() {
            eventsTable.ajax.reload();
        });
    
        $('#searchInput').on('keyup', function() {
            eventsTable.search(this.value).draw();
        });
    
        $('#resetFilters').on('click', function() {
            $('#dateFilter').val('');
            $('#statusFilter').val('');
            $('#searchInput').val('');
            eventsTable.search('').draw();
            
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            document.querySelector('[href="dashboard.html"]').classList.add('active');
            eventsTable.ajax.reload();
        });
    }

    document.getElementById('myEventsLink').addEventListener('click', function(e) {
        e.preventDefault();
        
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        this.classList.add('active');
    
        eventsTable.ajax.reload();
    });

    function getStatusBadge(event) {
        const now = new Date();
        const eventDate = new Date(event.event_date);
        
        if (eventDate < now) {
            return '<span class="badge badge-past">Past</span>';
        } else if (event.registered_attendees >= event.capacity) {
            return '<span class="badge badge-full">Full</span>';
        } else {
            return '<span class="badge badge-available">Available</span>';
        }
    }

    function getActionButtons(event) {
        let buttons = `
            <button class="btn btn-sm btn-evently" onclick="viewEvent(${event.event_id})">
                <i class="fas fa-eye"></i>
            </button>
        `;

        if (userData && userData.user_id === event.user_id) {
            buttons += `
                <button class="btn btn-sm btn-outline-evently ms-1" onclick="editEvent(${event.event_id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger ms-1" onclick="deleteEvent(${event.event_id})">
                    <i class="fas fa-trash"></i>
                </button>
            `;
        }

        return buttons;
    }

    function viewEvent(eventId) {
        window.location.href = `./event-details.html?id=${eventId}`;
    }

    async function editEvent(eventId) {
        try {
            const response = await fetch(`${ACTIVE_CONFIG.BASE_URL}/api/endpoints/event-details.php?id=${eventId}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                }
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('editEventId').value = data.event.event_id;
                document.getElementById('editDescription').value = data.event.description;
                document.getElementById('editEventDate').value = data.event.event_date;
                document.getElementById('editEventTime').value = data.event.event_time;
                document.getElementById('editRegistrationDeadline').value = data.event.registration_deadline;
                document.getElementById('editCapacity').value = data.event.capacity;

                const today = new Date().toISOString().split('T')[0];
                document.getElementById('editEventDate').min = today;
                document.getElementById('editRegistrationDeadline').min = today;

                const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
                modal.show();
            } else {
                alert('Error loading event details');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error loading event details');
        }
    }

    document.getElementById('editEventForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            return;
        }

        const submitButton = document.getElementById('updateEventButton');
        submitButton.disabled = true;
        submitButton.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Updating...
        `;

        try {
            const eventId = document.getElementById('editEventId').value;
            const formData = {
                event_id: eventId,
                description: document.getElementById('editDescription').value.trim(),
                event_date: document.getElementById('editEventDate').value,
                event_time: document.getElementById('editEventTime').value,
                registration_deadline: document.getElementById('editRegistrationDeadline').value,
                capacity: parseInt(document.getElementById('editCapacity').value)
            };

            const response = await fetch(`${ACTIVE_CONFIG.BASE_URL}/api/endpoints/update_event.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                },
                body: JSON.stringify(formData)
            });

            const data = await response.json();

            if (data.success) {
                showAlert('editEventAlert', 'Event updated successfully!', 'success');
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
                    // Reload events table
                    eventsTable.ajax.reload();
                }, 1500);
            } else {
                showAlert('editEventAlert', data.message || 'Failed to update event', 'danger');
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('editEventAlert', 'An error occurred. Please try again.', 'danger');
        } finally {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Update Event';
        }
    });

    function showAlert(elementId, message, type) {
        const alert = document.getElementById(elementId);
        alert.className = `alert alert-${type}`;
        alert.textContent = message;
        alert.classList.remove('d-none');

        if (type === 'success') {
            setTimeout(() => {
                alert.classList.add('d-none');
            }, 3000);
        }
    }

    function showdAlert(message, type = 'success') {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertElement = document.createElement('div');
        alertElement.id = alertId;
        alertElement.className = `custom-alert custom-alert-${type}`;
        alertElement.innerHTML = `
            ${message}
            <button class="close-btn" onclick="closeAlert('${alertId}')">&times;</button>
        `;
        
        alertContainer.appendChild(alertElement);
        
        setTimeout(() => alertElement.classList.add('show'), 10);
        
        setTimeout(() => closeAlert(alertId), 3000);
    }

    function closeAlert(alertId) {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            alertElement.classList.remove('show');
            setTimeout(() => alertElement.remove(), 300);
        }
    }

    async function deleteEvent(eventId) {
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        confirmDeleteBtn.onclick = async () => {
            try {
                confirmDeleteBtn.disabled = true;
                confirmDeleteBtn.innerHTML = `
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Deleting...
                `;

                const response = await fetch(`${ACTIVE_CONFIG.BASE_URL}/api/endpoints/delete_event.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`
                    },
                    body: JSON.stringify({
                        event_id: eventId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Hide the modal
                    bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal')).hide();
                    // Show success message
                    showdAlert('Event deleted successfully', 'success');
                    // Reload the events table
                    eventsTable.ajax.reload();
                } else {
                    showdAlert(data.message || 'Failed to delete event', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showdAlert('An error occurred while deleting the event', 'error');
            } finally {
                // Reset the delete button
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = 'Delete Event';
            }
        };

        const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
        deleteModal.show();
    }

    function logout() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('user_data');
        window.location.href = '../index.html';
    }
    $('#resetFilters').on('click', function() {
    $('#dateFilter').val('');
    $('#statusFilter').val('');
    $('#searchInput').val('');
    eventsTable.search('').columns().search('').draw();
    
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
    });
    document.querySelector('[href="dashboard.html"]').classList.add('active');
});