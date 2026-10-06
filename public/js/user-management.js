$(function () {
    const indexUrl = $('#um-filters').data('url');
    const config = window.UM || {};
    let searchTimer = null;
    let request = null;

    function csrfHeaders() {
        return {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
    }

    let activeUser = {};

    function rowUser(element) {
        const raw = $(element).closest('tr').attr('data-user');
        if (!raw) {
            return {};
        }
        try {
            return JSON.parse(raw);
        } catch (error) {
            return {};
        }
    }

    function userFromModalEvent(event) {
        const trigger = event.relatedTarget || event.originalEvent?.relatedTarget;
        const fromRow = rowUser(trigger);
        return Object.keys(fromRow).length ? fromRow : activeUser;
    }

    function bindSelectDropdowns() {
        $('[data-cu-select]').each(function () {
            const $wrap = $(this);
            if ($wrap.data('cuBound')) {
                return;
            }
            $wrap.data('cuBound', true);
            const $input = $wrap.find('select, input[type="hidden"]').first();
            const $label = $wrap.find('.js-select-label').first();
            $wrap.find('[data-value]').on('click', function () {
                const value = $(this).data('value');
                const label = $(this).data('label') || $(this).text().trim();
                $input.val(value === undefined ? '' : value);
                $label.text(label);
                $label.toggleClass('text-muted', !$input.val());
                $input.trigger('change');
                $wrap.find('.cu-dropdown-toggle').removeClass('is-invalid');
                $wrap.parent().find('.invalid-feedback').removeClass('d-block');
            });
        });
    }

    function currentParams() {
        const params = {};
        const search = $.trim($('#um-search').val());
        const location = $.trim($('#um-location').val());
        const role = $.trim($('#um-role').val());
        const status = $.trim($('#um-status').val());

        if (search) params.search = search;
        if (location) params.location = location;
        if (role) params.role = role;
        if (status) params.status = status;

        return params;
    }

    function hasFilters() {
        return Object.keys(currentParams()).length > 0;
    }

    function toggleClear() {
        $('#um-clear').prop('disabled', !hasFilters());
    }

    function applyStatistics(stats) {
        if (!stats) {
            return;
        }
        const format = (value) => Number(value || 0).toLocaleString();
        $('#um-stat-total').text(format(stats.total));
        $('#um-stat-active').text(format(stats.active));
        $('#um-stat-inactive').text(format(stats.inactive));
    }

    function dismissAlert($alert) {
        $alert.fadeOut(300, function () {
            $(this).addClass('d-none').removeAttr('style');
        });
    }

    function scheduleAlertDismiss($alert) {
        const previous = $alert.data('umTimer');
        if (previous) {
            clearTimeout(previous);
        }
        $alert.data('umTimer', setTimeout(function () {
            dismissAlert($alert);
        }, 10000));
    }

    function showFlash(message, type) {
        const $flash = $('#um-flash');
        if (!$flash.length || !message) {
            return;
        }
        $flash
            .stop(true, true)
            .removeClass('d-none alert-success alert-danger')
            .addClass(type === 'error' ? 'alert-danger' : 'alert-success')
            .text(message)
            .hide()
            .fadeIn(150);
        scheduleAlertDismiss($flash);
    }

    function fetchUsers(url) {
        const requestUrl = url || (indexUrl + ($.param(currentParams()) ? '?' + $.param(currentParams()) : ''));

        if (request) {
            request.abort();
        }

        $('#um-results').addClass('is-loading');
        request = $.ajax({
            url: requestUrl,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).done(function (html) {
            $('#um-results').html(html);
            if (window.history && window.history.pushState) {
                window.history.pushState({}, '', requestUrl);
            }
            toggleClear();
        }).always(function () {
            $('#um-results').removeClass('is-loading');
            request = null;
        });
    }

    function resetFilters() {
        $('#um-search').val('');
        $('#um-location, #um-role, #um-status').val('');
        $('#um-filters [data-cu-select]').each(function () {
            const $first = $(this).find('[data-value=""]').first();
            $(this).find('.js-select-label').text($first.data('label') || $first.text().trim());
        });
        toggleClear();
        fetchUsers(indexUrl);
    }

    function setSelectValue($wrap, value, fallbackLabel) {
        const $input = $wrap.find('input[type="hidden"]').first();
        const $label = $wrap.find('.js-select-label').first();
        const $option = $wrap.find('[data-value]').filter(function () {
            return String($(this).data('value')) === String(value);
        }).first();
        $input.val($option.length ? $option.data('value') : (value || ''));
        $label.text($option.data('label') || fallbackLabel || $option.text().trim() || 'Select');
        $label.toggleClass('text-muted', !$input.val());
    }

    function matchCampusLocation(value) {
        const options = [...document.querySelectorAll('.js-edit-location-option')];
        const exact = options.find((option) => option.dataset.value === value);
        if (exact) {
            return exact.dataset.value;
        }
        const campus = (value || '').split('-').pop()?.trim();
        if (!campus) {
            return value || '';
        }
        const match = options.find((option) => (option.dataset.value || '').includes(campus));
        return match?.dataset.value || value || '';
    }

    function grantedByEditRoles() {
        const selected = [...document.querySelectorAll('.js-edit-user-role:checked')].map((input) => input.value);
        return new Set(selected.flatMap((slug) => (config.rolePermissions || {})[slug] || []));
    }

    function updateEditRoleSummary() {
        const names = [...new Set([...document.querySelectorAll('.js-edit-user-role:checked')]
            .map((input) => input.closest('label')?.querySelector('.js-edit-role-name')?.textContent.trim())
            .filter(Boolean))];
        const summary = document.getElementById('editRolesSummary');
        if (!summary) {
            return;
        }
        summary.textContent = names.length ? names.join(', ') : 'Select roles';
        summary.classList.toggle('text-muted', names.length === 0);
        document.getElementById('editRolesDropdown')?.classList.remove('is-invalid');
        document.getElementById('editRolesError')?.classList.remove('d-block');
    }

    function updateEditPermissionSummary() {
        const granted = document.querySelectorAll('.js-edit-extra-permission:checked').length;
        const extra = [...document.querySelectorAll('.js-edit-extra-permission')]
            .filter((checkbox) => checkbox.checked && !checkbox.disabled).length;
        const summary = document.getElementById('editPermissionsSummary');
        if (!summary) {
            return;
        }
        if (!granted) {
            summary.textContent = 'Select extra permissions';
            summary.classList.add('text-muted');
            return;
        }
        summary.textContent = extra
            ? `${granted} selected (${extra} extra)`
            : `${granted} from selected roles`;
        summary.classList.remove('text-muted');
    }

    function refreshEditPermissions(extras) {
        const granted = grantedByEditRoles();
        const extraSet = new Set(extras || []);

        document.querySelectorAll('.js-edit-perm-row').forEach((row) => {
            const slug = row.dataset.permission;
            const checkbox = row.querySelector('.js-edit-extra-permission');
            const source = row.querySelector('.js-edit-perm-source');
            const fromRole = granted.has(slug);

            if (fromRole) {
                checkbox.checked = true;
                checkbox.disabled = true;
                source.textContent = 'Role';
            } else {
                checkbox.disabled = false;
                checkbox.checked = extraSet.has(slug);
                source.textContent = checkbox.checked ? 'Extra' : 'Off';
            }
            row.classList.toggle('is-granted', fromRole);
            row.classList.toggle('is-extra', !fromRole && checkbox.checked);
        });

        updateEditRoleSummary();
        updateEditPermissionSummary();
    }

    function clearEditFieldErrors() {
        $('#editUserForm .is-invalid').removeClass('is-invalid');
        $('#editUserForm .invalid-feedback').removeClass('d-block');
        $('#editRolesError').text('Select at least one role.');
        $('#editSltEmployeeError').text('Select whether this person is an SLT employee.');
        $('#editLocationError').text('Select a location.');
        $('#editServiceIdError').text('Enter an employee ID.');
    }

    function applyEditFieldErrors(errors) {
        const fields = {
            name: '#editName',
            nic: '#editNic',
            email: '#editEmail',
            phone: '#editPhone',
            designation: '#editDesignation',
            service_id: '#edit_service_id',
        };

        Object.entries(fields).forEach(([key, selector]) => {
            const messages = errors[key];
            if (!messages?.length) {
                return;
            }
            const $input = $(selector);
            $input.addClass('is-invalid');
            $input.closest('[class*="col-"]').find('.invalid-feedback').first().text(messages[0]).addClass('d-block');
        });

        if (errors.slt_employee?.length) {
            $('#edit_slt_employee').closest('.dropdown').find('.cu-dropdown-toggle').addClass('is-invalid');
            $('#editSltEmployeeError').text(errors.slt_employee[0]).addClass('d-block');
        }
        if (errors.location?.length) {
            $('#editLocation').closest('.dropdown').find('.cu-dropdown-toggle').addClass('is-invalid');
            $('#editLocationError').text(errors.location[0]).addClass('d-block');
        }
        if (errors.user_roles?.length) {
            $('#editRolesDropdown').addClass('is-invalid');
            $('#editRolesError').text(errors.user_roles[0]).addClass('d-block');
        }
    }

    function restoreEditValidation() {
        const old = config.oldInput;
        if (!config.editUpdateUrl || !old) {
            return;
        }

        fillEditModal({
            update_url: config.editUpdateUrl,
            name: old.name,
            nic: old.nic,
            email: old.email,
            phone: old.phone,
            designation: old.designation,
            service_id: old.service_id,
            slt_employee: old.slt_employee,
            location: old.location,
            role_slugs: old.user_roles || [],
            extras: old.extra_permissions || [],
        });
        applyEditFieldErrors(config.errors || {});

        const modalEl = document.getElementById('editUserModal');
        if (modalEl && window.bootstrap?.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function setEditSltMode() {
        const isSltEmployee = $('#edit_slt_employee').val() === 'yes';
        const $employeeId = $('#edit_service_id');
        $employeeId.prop({ required: isSltEmployee, disabled: !isSltEmployee });
        $employeeId.attr('placeholder', isSltEmployee ? 'e.g. 010375' : 'Enabled when SLT employee is Yes');
        $('#editEmployeeIdRequired').toggleClass('d-none', !isSltEmployee);
        $('#editLookupEmployee').toggleClass('d-none', !isSltEmployee);
        $('#editName, #editEmail, #editPhone, #editDesignation').prop('readOnly', isSltEmployee);

        if (!isSltEmployee) {
            $('#editLookupMessage').text('').attr('class', 'form-text');
            $('#editEmployeeDetails').removeClass('is-visible');
            $('#editName, #editEmail, #editPhone, #editDesignation').prop('readOnly', false);
        }
    }

    function fillViewModal(user) {
        $('#viewSltEmployee').val(user.slt_employee === 'yes' ? 'Yes' : 'No');
        $('#viewServiceId').val(user.service_id || '—');
        $('#viewName').val(user.name || '—');
        $('#viewNic').val(user.nic || '—');
        $('#viewEmail').val(user.email || '—');
        $('#viewPhone').val(user.phone || '—');
        $('#viewDesignation').val(user.designation || '—');
        $('#viewLocation').val(user.location || '—');
        $('#viewRoles').val([...new Set(user.role_names || [])].join(', ') || 'Unassigned');
        const permissions = user.permissions || [];
        $('#viewPermissions').html(permissions.length
            ? permissions.map((item) => `<div>${$('<div>').text(item).html()}</div>`).join('')
            : '<span class="text-muted">No permissions assigned</span>');
    }

    function fillEditModal(user) {
        clearEditFieldErrors();
        $('#editUserForm').attr('action', user.update_url);
        $('#editName').val(user.name || '');
        $('#editNic').val(user.nic || '');
        $('#editEmail').val(user.email || '');
        $('#editPhone').val(user.phone || '');
        $('#editDesignation').val(user.designation || '');
        $('#edit_service_id').val(user.service_id || '');
        $('#editLookupMessage').text('').attr('class', 'form-text');
        $('#editEmployeeDetails').removeClass('is-visible');
        $('#editEmployeeDetailsBody').empty();

        setSelectValue($('#edit_slt_employee').closest('[data-cu-select]'), user.slt_employee || '', 'Select an option');
        const locationValue = matchCampusLocation(user.location || '');
        setSelectValue($('#editLocation').closest('[data-cu-select]'), locationValue, user.location || 'Select location');
        setEditSltMode();
        if (user.slt_employee === 'yes') {
            $('#edit_service_id').val(user.service_id || '');
        }

        $('.js-edit-user-role').prop('checked', false);
        [...new Set(user.role_slugs || [])].forEach((slug) => {
            $(`.js-edit-user-role[value="${slug}"]`).prop('checked', true);
        });
        refreshEditPermissions(user.extras || []);
    }

    async function lookupEditEmployee() {
        const employeeId = $('#edit_service_id').val().trim();
        const $message = $('#editLookupMessage');
        if (!employeeId) {
            $message.text('Enter an Employee ID first.').attr('class', 'form-text text-danger');
            return;
        }

        $('#editLookupEmployee').prop('disabled', true);
        $message.text('Looking up employee details...').attr('class', 'form-text text-muted');

        try {
            const response = await fetch(`${config.lookupUrl}?employee_id=${encodeURIComponent(employeeId)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Employee lookup failed.');

            ['name', 'email', 'phone', 'designation'].forEach((field) => {
                const id = { name: 'editName', email: 'editEmail', phone: 'editPhone', designation: 'editDesignation' }[field];
                if (data[field]) document.getElementById(id).value = data[field];
            });
            if (data.service_id) $('#edit_service_id').val(data.service_id);
            if (data.nic) $('#editNic').val(data.nic);

            const labels = {
                organization: 'Organization',
                section: 'Section',
                division: 'Division',
                grade: 'Grade',
                cost_centre: 'Cost centre',
                work_location: 'Work location',
            };
            $('#editEmployeeDetailsBody').html(Object.entries(labels).map(([key, label]) => {
                const value = data.details?.[key] || '—';
                return `<div class="col-6 col-md-4"><div class="text-muted">${label}</div><div class="fw-medium">${value}</div></div>`;
            }).join(''));
            $('#editEmployeeDetails').addClass('is-visible');
            $message
                .text(data.mock
                    ? 'Development employee data loaded. The live ERP API will be used in production.'
                    : 'Employee details loaded from SLT ERP.')
                .attr('class', 'form-text text-success');
        } catch (error) {
            $message.text(error.message).attr('class', 'form-text text-danger');
        } finally {
            $('#editLookupEmployee').prop('disabled', false);
        }
    }

    $('#um-filters').on('submit', function (event) {
        event.preventDefault();
        fetchUsers();
    });

    $('#um-search').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(fetchUsers, 300);
        toggleClear();
    });

    $('#um-location, #um-role, #um-status').on('change', function () {
        fetchUsers();
        toggleClear();
    });

    $('#um-clear').on('click', function () {
        if (!$(this).prop('disabled')) {
            resetFilters();
        }
    });

    $(document).on('click', '#um-results .pagination a', function (event) {
        event.preventDefault();
        const href = $(this).attr('href');
        if (href && href !== '#') {
            fetchUsers(href);
        }
    });

    window.addEventListener('popstate', function () {
        window.location.reload();
    });

    $(document).on('click', '.js-resend-setup', function () {
        const $button = $(this);
        if ($button.prop('disabled')) {
            return;
        }

        const user = rowUser(this);
        $button.prop('disabled', true);

        $.ajax({
            url: user.resend_setup_url,
            method: 'POST',
            headers: csrfHeaders(),
        }).done(function (response) {
            showFlash(response.status, 'success');
        }).fail(function (xhr) {
            showFlash(xhr.responseJSON?.message || 'The password setup email could not be sent.', 'error');
        }).always(function () {
            if (!user.has_set_password) {
                $button.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.js-toggle-user', function () {
        const user = rowUser(this);
        $.ajax({
            url: user.toggle_url,
            method: 'POST',
            headers: csrfHeaders(),
        }).done(function (response) {
            showFlash(response.status, 'success');
            applyStatistics(response.statistics);
            fetchUsers(window.location.href);
        }).fail(function (xhr) {
            showFlash(xhr.responseJSON?.message || 'Could not update user status.', 'error');
        });
    });

    $(document).on('click', '.js-delete-user', function () {
        if ($(this).prop('disabled')) {
            return;
        }

        const user = rowUser(this);
        const name = user.name || 'this user';

        const confirmDelete = typeof Swal === 'undefined'
            ? Promise.resolve({ isConfirmed: window.confirm('Delete ' + name + '? This cannot be undone.') })
            : Swal.fire({
                icon: 'warning',
                title: 'Delete this user?',
                text: name + ' will be permanently deleted. This cannot be undone.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545',
                reverseButtons: true,
                customClass: { popup: 'um-swal' },
            });

        Promise.resolve(confirmDelete).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: function () {
                        Swal.showLoading();
                    },
                });
            }
            $.ajax({
                url: user.delete_url,
                method: 'DELETE',
                headers: csrfHeaders(),
            }).done(function (response) {
                if (typeof Swal !== 'undefined') {
                    Swal.close();
                }
                showFlash(response.status, 'success');
                applyStatistics(response.statistics);
                fetchUsers(window.location.href);
            }).fail(function (xhr) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete failed',
                        text: xhr.responseJSON?.message || 'Could not delete this user.',
                    });
                } else {
                    showFlash(xhr.responseJSON?.message || 'Could not delete this user.', 'error');
                }
            });
        });
    });

    $(document).on('click', '.js-view-user, .js-edit-user, .js-reset-user', function () {
        activeUser = rowUser(this);
    });

    $('#viewUserModal').on('show.bs.modal', function (event) {
        fillViewModal(userFromModalEvent(event));
    });

    $('#editUserModal').on('show.bs.modal', function (event) {
        fillEditModal(userFromModalEvent(event));
    });

    $('#edit_slt_employee').on('change', function () {
        if ($(this).val() !== 'yes') {
            $('#edit_service_id').val('');
        }
        setEditSltMode();
    });

    $('#editLookupEmployee').on('click', lookupEditEmployee);
    $('#edit_service_id').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            lookupEditEmployee();
        }
    });

    $(document).on('change', '.js-edit-user-role, .js-edit-extra-permission', function () {
        const extras = [...document.querySelectorAll('.js-edit-extra-permission')]
            .filter((item) => item.checked && !item.disabled)
            .map((item) => item.value);
        refreshEditPermissions(extras);
    });

    $('#editUserForm').on('submit', function (event) {
        const missingSlt = !$('#edit_slt_employee').val();
        const missingLocation = !$('#editLocation').val();
        const missingRole = !$('.js-edit-user-role:checked').length;

        if (missingSlt || missingLocation || missingRole) {
            event.preventDefault();
            $('#edit_slt_employee').closest('.dropdown').find('.cu-dropdown-toggle').toggleClass('is-invalid', missingSlt);
            $('#editLocation').closest('.dropdown').find('.cu-dropdown-toggle').toggleClass('is-invalid', missingLocation);
            $('#editRolesDropdown').toggleClass('is-invalid', missingRole);
            $('#editSltEmployeeError').toggleClass('d-block', missingSlt);
            $('#editLocationError').toggleClass('d-block', missingLocation);
            $('#editRolesError').toggleClass('d-block', missingRole);
            return;
        }

        $('.js-edit-extra-permission').prop('disabled', false);
        if ($('#edit_service_id').prop('disabled')) {
            $('#edit_service_id').prop('disabled', false);
            if ($('#edit_slt_employee').val() !== 'yes') {
                $('#edit_service_id').val('');
            }
        }
    });

    function bindPasswordToggles(scope) {
        $(scope).find('[data-password-toggle]').each(function () {
            const $button = $(this);
            if ($button.data('umBound')) {
                return;
            }
            $button.data('umBound', true);
            $button.on('click', function () {
                const input = document.getElementById($button.data('passwordToggle'));
                const icon = $button.find('i')[0];
                if (!input || !icon) {
                    return;
                }
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                icon.classList.toggle('ti-eye', showing);
                icon.classList.toggle('ti-eye-off', !showing);
                $button.attr('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
    }

    function clearResetPasswordErrors() {
        $('#newPassword, #newPasswordConfirmation').removeClass('is-invalid');
        $('#newPasswordError, #newPasswordConfirmationError').removeClass('d-block');
    }

    function showResetPasswordErrors(errors) {
        clearResetPasswordErrors();
        if (errors.password?.length) {
            $('#newPassword').addClass('is-invalid');
            $('#newPasswordError').text(errors.password[0]).addClass('d-block');
        }
        if (errors.password_confirmation?.length) {
            $('#newPasswordConfirmation').addClass('is-invalid');
            $('#newPasswordConfirmationError').text(errors.password_confirmation[0]).addClass('d-block');
        }
    }

    $('#resetPasswordModal').on('show.bs.modal', function (event) {
        const user = userFromModalEvent(event);
        $('#resetPasswordForm').attr('action', user.reset_url);
        $('#resetPasswordUserName').text(user.name || '');
        $('#resetPasswordUserEmail').text(user.email || '');
        $('#resetPasswordForm')[0].reset();
        $('#newPassword, #newPasswordConfirmation').attr('type', 'password');
        $('#resetPasswordForm .um-password-toggle i').attr('class', 'ti ti-eye');
        $('#resetPasswordForm .um-password-toggle').attr('aria-label', 'Show password');
        clearResetPasswordErrors();
        bindPasswordToggles('#resetPasswordModal');
    });

    $('#resetPasswordForm').on('submit', function (event) {
        event.preventDefault();
        const $form = $(this);
        const $submit = $('#resetPasswordSubmit');
        const password = $('#newPassword').val();
        const confirmation = $('#newPasswordConfirmation').val();

        if (password.length < 8) {
            showResetPasswordErrors({ password: ['Use at least 8 characters.'] });
            return;
        }
        if (password !== confirmation) {
            showResetPasswordErrors({ password_confirmation: ['The passwords do not match.'] });
            return;
        }

        clearResetPasswordErrors();
        $submit.prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize(),
            headers: csrfHeaders(),
        }).done(function (response) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('resetPasswordModal'));
            modal?.hide();
            showFlash(response.status, 'success');
            fetchUsers(window.location.href);
        }).fail(function (xhr) {
            const errors = xhr.responseJSON?.errors;
            if (errors) {
                showResetPasswordErrors(errors);
                return;
            }
            showFlash(xhr.responseJSON?.message || 'The password could not be reset.', 'error');
        }).always(function () {
            $submit.prop('disabled', false);
        });
    });

    $('#viewUserModal, #editUserModal, #resetPasswordModal').appendTo(document.body);

    bindSelectDropdowns();
    toggleClear();
    restoreEditValidation();

    $('.um-auto-alert').each(function () {
        const $alert = $(this);
        if (!$alert.hasClass('d-none') && $.trim($alert.text())) {
            scheduleAlertDismiss($alert);
        }
    });
});
