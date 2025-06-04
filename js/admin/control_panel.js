$(document).ready(function () {
     flatpickr("#dateRange", {
        mode: "range",
        dateFormat: "Y-m-d",
        minDate: "today",
    });

    // Initialize flatpickr for the report date modal (MOVED OUTSIDE)
    flatpickr("#reportDate", {
        dateFormat: "Y-m-d",
        maxDate: "today"
    });

    // Modal event handlers (MOVED OUTSIDE)
    $('#generateReportBtn').click(function () {
        $('#reportSpecForm')[0].reset();
        $('#reportSpecModal').modal('show');
    });

    $('#reportSpecForm').on('submit', function (e) {
        e.preventDefault();

        const reportDate = $('#reportDate').val().trim();
        const programId = $('#programSelect').val();

        if (!reportDate) {
            alert("Please select a date for the report.");
            return;
        }
        if (!programId) {
            alert("Please select a program.");
            return;
        }

        $('#reportGenerationLoading').removeClass('d-none');
        $('#generateReportBtn').prop('disabled', true);

        $.ajax({
            url: '../php-api/GenerateReport.php',
            method: 'POST',
            dataType: 'json',
            data: {
                reportDate: reportDate,
                programId: programId
            },
            success: function (response) {
                $('#reportGenerationLoading').addClass('d-none');
                $('#generateReportBtn').prop('disabled', false);

                if (response.success) {
                    const { programCode, date, stats } = response.data;

                    const { jsPDF } = window.jspdf;
                    const doc = new jsPDF({
                        unit: 'pt',
                        format: 'letter'
                    });

                    const headerLines = [
                        'STI College Balagtas',
                        'Student Monitoring Report',
                        `for ${programCode} on ${date}`
                    ];
                    doc.setFontSize(24);
                    doc.setFont("helvetica", "bold");
                    doc.text(headerLines, 40, 60);

                    doc.setFontSize(16);
                    doc.setFont("helvetica", "normal");
                    let yPos = 150;
                    doc.text(`Number of Students Attended: ${stats.attended}`, 40, yPos);
                    yPos += 30;
                    doc.text(`Total Violations: ${stats.totalViolations}`, 40, yPos);
                    yPos += 30;
                    doc.text(`Pending Violations: ${stats.pendingViolations}`, 40, yPos);
                    yPos += 30;
                    doc.text(`Reviewed Violations: ${stats.reviewedViolations}`, 40, yPos);

                    const safeProg = programCode.replace(/\s+/g, '_');
                    const filename = `Report_${safeProg}_${date}.pdf`;
                    doc.save(filename);

                    $('#reportSpecModal').modal('hide');
                } else {
                    alert("Failed to generate report: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                $('#reportGenerationLoading').addClass('d-none');
                $('#generateReportBtn').prop('disabled', false);
                console.error("AJAX Error:", error);
                alert("An error occurred while generating the report.");
            }
        });
    });

    let isClearing = false;
    let turnOn;

    $('#dateRange').on('input', function () {
        if (isClearing) return;
        if ($(this).val()) {
            isClearing = true;
            $('#weekDay').val('');
            isClearing = false;
        }
    });

    $('#weekDay').on('change', function () {
        if (isClearing) return;
        if ($(this).val()) {
            isClearing = true;
            $('#dateRange').val('');
            isClearing = false;
        }
    });

    $('#submitDateEvent').click(() => {
        let dateRange = $('#dateRange').val() || null;
        let weekDay = $('#weekDay').val() || null;
        let description = $('#description').val() || null;

        if((!dateRange && !weekDay) || (dateRange && weekDay)) {
            showResponseMessage('#responseMessage', 'Please choose either a date range or a weekday — not both.', 'danger');
            return;
        }

        let queryData = {
            description: description
        };

        if (dateRange) {
            const [start, end] = dateRange.split(' to ');
            queryData.startDate = start;
            queryData.endDate = end;
        } else if (weekDay) {
            queryData.weekDay = weekDay;
        }

        $.ajax({
            url: '../php-api/AddExceptionDays.php',
            method: 'POST',
            dataType: 'json',
            data: queryData,
            success: (response) => {
                showResponseMessage('#responseMessage', response.message, 'success');
                fetchExceptionDays();
            },
            error: (xhr, status, error) => {
                console.error('AJAX Error:', error);
                
                const responseText = xhr.responseText;

                if (responseText && responseText.includes('Duplicate entry')) {
                    showResponseMessage('#responseMessage', 'Please choose another date as it has already been set!', 'danger');
                } else {
                    showResponseMessage('#responseMessage', 'Something went wrong. Please try again.', 'danger');
                }
            }
        });
    });

    $("#automaticChecking").change(function () {
        $.ajax({
            url: '../php-api/UpdateCheckingBehavior.php',
            method: 'POST',
            data: JSON.stringify({ turnOn: this.checked }),
            dataType: 'json',
            success: (response) => {
                let checkingBehavior = this.checked ? 'Automatic' : 'Manual';
                showResponseMessage('#responseMessage', 'Updated to ' + checkingBehavior, 'success');
                $('#automaticCheckingText').text(checkingBehavior + ' Checking');
            },
            error: (xhr, status, error) => {
                showResponseMessage('#responseMessage', 'Error updating checking behavior.', 'danger');
                console.error('AJAX Error:', error);
            }
        });

        if (this.checked) {
            $('#manualUpdateViolationsArea').slideUp();
        } else {
            $('#manualUpdateViolationsArea').slideDown();
        }
    });

    const fetchCheckingBehavior = () => {
        $.ajax({
            url: '../php-api/ReadCheckingBehavior.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                let checkingBehavior = response.turnOn ? 'Automatic' : 'Manual';
                $('#automaticCheckingText').text(checkingBehavior + ' Checking');
                $("#automaticChecking").prop('checked', turnOn = response.turnOn);
                if (response.turnOn) {
                    $('#manualUpdateViolationsArea').slideUp();
                } else {
                    $('#manualUpdateViolationsArea').slideDown();
                }

            },
            error: (xhr, status, error) => {
                console.error('AJAX Error:', error);
            }
        });
    };

    const fetchExceptionDays = () => {
        $.ajax({
            url: '../php-api/ReadExceptionDays.php',
            method: 'GET',
            dataType: 'json',
            success: (response) => {
                displayTable(response.data);
            },
            error: (xhr, status, error) => {
                console.error('AJAX Error:', error);
            }
        });
    };

    // Function to show toast notifications
    const showToast = (message, type = 'info') => {
        // Create toast HTML
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'danger' ? 'danger' : 'info'} border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="4000">
                <div class="d-flex">
                    <div class="toast-body">
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        // Create toast container if it doesn't exist
        if (!$('#toast-container').length) {
            $('body').append('<div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>');
        }

        // Add toast to container
        $('#toast-container').append(toastHtml);

        // Initialize and show toast
        const toastElement = new bootstrap.Toast(document.getElementById(toastId));
        toastElement.show();

        // Remove toast element after it's hidden
        $('#' + toastId).on('hidden.bs.toast', function () {
            $(this).remove();
        });
    };

    $('#manualUpdateViolations').click(function() {
        $('#manualUpdateViolationsLoading').removeClass('d-none');
        $.ajax({
            url: "http://127.0.0.1:8000/predict/manual_folder/",
            method: "GET",
            processData: false,
            contentType: false,
            success: function (response) {
                $('#manualUpdateViolationsLoading').addClass('d-none');
                
                // Check if response is empty or has no data
                if (!response || Object.keys(response).length === 0) {
                    showToast('There is nothing to scan', 'danger');
                    return;
                }

                // Check if any folder has predictions
                let hasAnyPredictions = false;
                Object.values(response).forEach(predictions => {
                    if (predictions && predictions.length > 0) {
                        hasAnyPredictions = true;
                    }
                });

                if (!hasAnyPredictions) {
                    showToast('No images found to process in manual_uploads folder.', 'info');
                    return;
                }

                let processedCount = 0;
                let totalViolations = 0;

                Object.entries(response).forEach(([key, predictions]) => {
                const studentID = key.split('-').pop(); // get ID after last hyphen
                const studentName = key.split('-').slice(0, -1).join('_'); // student name
                    
                predictions.forEach(pred => {
                    const violationType = pred.prediction.toLowerCase() === 'no_uniform' ? 'WithoutUniform' : null;
                    const fileName = pred.filename;
                    const studentNameFolder = key;

                    console.log('Processing prediction:', pred); 
                    console.log('Violation type:', violationType); 

                    if (violationType) {
                        totalViolations++;
                        const violationForm = new FormData();
                        violationForm.append('ViolationType', violationType); // 0 means Non-Uniform
                        violationForm.append('StudentID', studentID);
                        violationForm.append('StudentFolderName', studentNameFolder);
                        violationForm.append('FileName', fileName);

                        $.ajax({
                            url: '../php-api/AddViolationManually.php',
                            method: 'POST',
                            data: violationForm,
                            processData: false,
                            contentType: false,
                            success: function (response) {
                                processedCount++;
                                if (processedCount === totalViolations) {
                                    showResponseMessage('#responseMessage', `Successfully processed ${totalViolations} violation(s).`, 'success');
                                    showToast(`Manual violation check completed! Found ${totalViolations} violations.`, 'success');
                                }
                            },
                            error: function () {
                                processedCount++;
                                showResponseMessage('#responseMessage', `Failed to insert violation for ${studentName}`, 'danger');
                                if (processedCount === totalViolations) {
                                    showToast('Manual violation check completed with some errors.', 'danger');
                                }
                            }
                        });
                    }
                });
                });

                // If no violations found but images were processed
                if (totalViolations === 0) {
                    showResponseMessage('#responseMessage', 'No uniform violations detected in the uploaded images.', 'success');
                    showToast('Manual check completed - no violations found.', 'success');
                }
            },
            error: function (xhr, status, error) {
                $('#manualUpdateViolationsLoading').addClass('d-none');
                console.error('Violation POST failed:', xhr.responseText);
                
                if (xhr.status === 404) {
                    showToast('Manual uploads folder not found or empty.', 'danger');
                } else if (xhr.status === 500) {
                    showToast('Server error occurred while processing images.', 'danger');
                } else {
                    showToast('Failed to connect to the prediction service.', 'danger');
                }
                
                showResponseMessage('#responseMessage', 'Failed to process manual violations.', 'danger');
            }
            });

    });

    const displayTable = (data) => {
        let tableBody = $('.exception-days-table-body');
        tableBody.empty();

        if (data.length === 0) {
            tableBody.append('<tr><td colspan="3">No exception days found</td></tr>');
        } else {
            data.forEach(eday => {
                let displayStartDate = eday.StartDate ? eday.StartDate : '';
                let displayEndDate = eday.EndDate ? eday.EndDate : '';
                let displayWeekday = eday.Weekday ? eday.Weekday : '';
                let displayDescription = eday.Description ? eday.Description : '';

                let row = `
                    <tr data-id="${eday.id}" 
                        data-dates="${eday.Dates}" 
                        data-weekday="${eday.Weekday}" 
                        data-description="${eday.Description}">
                        <td>${(displayStartDate) ? (displayStartDate + " to " + displayEndDate) : displayWeekday}</td>
                        <td>${displayDescription}</td>
                        <td><button class="btn btn-danger" data-id="${eday.id}" onclick="deleteExceptionDay(this)">&times;</button></td>
                    </tr>`;

                tableBody.append(row);
            });
        }
    };

    window.deleteExceptionDay = (button) => {
        var exceptionDayId = $(button).data('id');
        // if (confirm('Are you sure you want to delete this date?')) {
            $.ajax({
                url: '../php-api/DeleteExceptionDay.php',
                method: 'POST',
                data: { id: exceptionDayId },
                success: function(response) {
                    if (response.status === 'success') {
                        const $row = $(button).closest('tr');
                        const $tableBody = $row.closest('tbody');

                        $row.remove();

                        if ($tableBody.find('tr').length === 0) {
                            $tableBody.append('<tr><td colspan="3">No exception days found</td></tr>');
                        }
                        //showResponseMessage('#responseMessage', 'Date deleted successfully!', 'success');
                    } else {
                        showResponseMessage('#responseMessage', 'Failed to delete violation: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showResponseMessage('#responseMessage', 'Error deleting violation.', 'danger');
                }
            });
        // }
    }

    $('#archiveRecordsBtn').click(function () {
        // Show loading spinner
        $('#archiveLoading').removeClass('d-none');

        $.ajax({
            url: '../php-api/ArchiveStudentRecords.php',
            method: 'POST',
            dataType: 'json',
            data: { archiveImages: true }, //archive images too
            success: function (response) {
                $('#archiveLoading').addClass('d-none');

                if (response.success) {
                    showResponseMessage('#responseMessage', response.message || 'Records archived successfully.', 'success');
                } else {
                    showResponseMessage('#responseMessage', response.message || 'Archiving failed.', 'danger');
                }
            },
            error: function (xhr, status, error) {
                $('#archiveLoading').addClass('d-none');
                console.error('AJAX Error:', error);
                showResponseMessage('#responseMessage', 'An error occurred while archiving student records.', 'danger');
            }
        });
    });


    fetchExceptionDays();
    fetchCheckingBehavior();
});