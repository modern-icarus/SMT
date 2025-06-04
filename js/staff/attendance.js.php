<script type="text/javascript">
    $(document).ready(function(){
        let stream;
        let countRestart = 0;
        let homeRedirect = true;
        let exceptionDay = false;
        let isManualMode = false;

        const canvas = document.getElementById('canvas');
        const video = document.getElementById('video');
        const startCameraButton = document.getElementById('startCamera');
        const captureButton = document.getElementById('capture');
        const timerElement = document.getElementById('timer');
        const instructionElement = document.getElementById('instruction');

        window.fetchStudent = function() {
            $.ajax({
            url: '../php-api/ReadStudentRecord.php', 
            method: 'GET',
            data: { StudentID: <?php echo $_SESSION['student']['StudentID']; ?> },
            success: function(response) {
                if (response.status === 'success') {
                    var student = response.student;
                    var violations = response.violations;

                    // Populate student info in the modal
                    $('#studentNoDisplay').text(student.StudentID);
                    $('#studentNameDisplay').text(student.StudentName);
                    $('#studentYearDisplay').text(student.Year);
                    $('#studentCourseDisplay').text(student.ProgramCode);
                    $('#studentTotalViolations').text(response.violationCount);
                    if(response.violationCount >= 1) {
                        $('#totalViolationsText').text('Total Violation:');
                    }

                } else {
                    alert('Failed to fetch violation details: ' + response.message);
                }
            },
            error: function() {
                alert('Error fetching violation details.');
            }
            });
        };

        const hideButtons = () => {
            $('#camera-ui').hide();
        };

        const goHome = () => {
            setTimeout(() => {
                if(homeRedirect) {
                window.location.href = "../staff";
                }
            }, 1500);
        };

        const preCheckAttendance = () => {
            return $.ajax({
                url: '../php-api/PreCheckAttendance.php',
                method: 'GET',
                dataType: 'json',
                processData: false,
                contentType: false
            }).then(response => {
                exceptionDay = response.exceptionDay;
                isManualMode = response.isManualMode;

                if (exceptionDay) {
                    $('#camera-section').remove();
                    processStatus(response);
                    return false; // no need to run camera
                }

                processStatus(response, true); // Start camera if not exception day

                if(response.attendanceStatus == "attended_recently" || response.attendanceStatus == "attended_and_timed_out" || response.attendanceStatus == "timeout_updated") {
                    return false; // don't run camera
                }
                return true; // run camera
            }).catch(error => {
                console.error('AJAX Error:', error);
                return true; // default to true if error
            });
        };

        const fetchExceptionDays = async () => {
            const context = canvas.getContext('2d');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(async function (blob) {
                const formData = new FormData();
                formData.append('file', blob, 'capture.jpg');

                $.ajax({
                    url: '../php-api/CheckIfExceptionDay.php',
                    method: 'POST',
                    dataType: 'json',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: (response) => {
                        exceptionDay = response.exceptionDay;
                        isManualMode = response.isManualMode;

                        if (exceptionDay) {
                            $('#camera-section').remove();
                            processStatus(response);
                        } else {
                            processStatus(response, true); // Start camera if not an exception day
                        }
                    },
                    error: (xhr, status, error) => {
                        console.error('AJAX Error:', error);
                    }
                });
            }, 'image/jpeg');
        };


    const startCamera = async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;

            captureButton.disabled = false;

            video.addEventListener('loadeddata', () => {
                // Camera is ready, now fetch exception day
                fetchExceptionDays(); // Moved here

                if (isManualMode) {
                    // Immediate capture for manual mode
                    captureButton.click();
                    return;
                }

                const checkInterval = setInterval(() => {
                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = video.videoWidth;
                    tempCanvas.height = video.videoHeight;
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.drawImage(video, 0, 0, tempCanvas.width, tempCanvas.height);

                    const frame = tempCtx.getImageData(0, 0, tempCanvas.width, tempCanvas.height);
                    const pixels = frame.data;

                    let total = 0;
                    for (let i = 0; i < pixels.length; i += 4) {
                        const r = pixels[i];
                        const g = pixels[i + 1];
                        const b = pixels[i + 2];
                        const brightness = (r + g + b) / 3;
                        total += brightness;
                    }

                    const avgBrightness = total / (pixels.length / 4);

                    if (avgBrightness > 30) {
                        clearInterval(checkInterval);
                        let countdown = 1;
                        timerElement.textContent = countdown;

                        const countdownInterval = setInterval(() => {
                            countdown--;
                            timerElement.textContent = countdown;

                            if (countdown <= 0) {
                                clearInterval(countdownInterval);
                                captureButton.click();
                            }
                        }, 1000);
                    }
                }, 500);
            });

            captureButton.onclick = async () => {
                const context = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                context.drawImage(video, 0, 0, canvas.width, canvas.height);

                stopCamera();
                await sendToPrediction();
            };
        } catch (error) {
            console.error('Error accessing the camera:', error);
            alert('Unable to access the camera. Please allow camera access.');
        }
    };


        function stopCamera() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                video.style.display = 'none';
                $('#canvas').removeClass('d-none');
                captureButton.disabled = true;
            }
        }

        async function sendToPrediction() {
            const loadingDiv = document.getElementById('loading');
            loadingDiv.style.display = 'flex'; // Show loading

            canvas.toBlob(async function (blob) {
                const formData = new FormData();
                formData.append('file', blob, 'capture.jpg');

                try {
                const response = await fetch('http://127.0.0.1:8000/predict/', {  // Make sure the URL is correct
                    method: 'POST',
                    body: formData
                });

                const result = await response.json(); // Make sure the result is properly assigned

                // Now that we have the 'result', we can use it to set the uniform status
                formData.append('uniformStatus', result.prediction === "Uniform" ? 1 : 0);  // Send uniform status based on prediction result

                console.log(result.prediction);
                console.log(result.prediction === "Uniform" ? 1 : 0);

                // Send the image and uniform status to your PHP script
                const phpResponse = await fetch('../php-api/AddAttendance.php', { 
                    method: 'POST',
                    body: formData
                });

                const phpResult = await phpResponse.json();
                loadingDiv.style.display = 'none'; // Hide loading

                

                if(result.error == "No person detected") {
                    showResponseMessage("No person detected! Please Try Again!", phpResult.level);
                    startCameraButton.click();
                } else {
                    showResponseMessage(phpResult.message, phpResult.level, false);
                    hideButtons();
                }

                goHome();
                } catch (error) {
                console.error('Prediction error:', error);
                loadingDiv.style.display = 'none'; // Hide loading
                showResponseMessage("⚠️ Error sending image for prediction.", 'danger');
                setTimeout(() => {
                    if(countRestart <= 1) {
                    startCameraButton.click();
                    } else {
                    if(homeRedirect) {
                        window.location.href = "../staff";
                    }
                    }
                    
                    countRestart++;
                }, 1000);
                
                }
            }, 'image/jpeg');
        }

        function showResponseMessage(message, type, fadeOut = true, id = "responseMessage") {
            const msgBox = document.getElementById(id);
            msgBox.className = `modern-alert ${type === 'success' ? 'modern-alert-success' : 'modern-alert-danger'} fs-3`;
            msgBox.textContent = message;
            msgBox.style.display = 'block';
            msgBox.style.opacity = '1';

            if(fadeOut) {
                // Fade out after 2 seconds
                setTimeout(() => {
                    msgBox.style.opacity = '0';
                    setTimeout(() => msgBox.style.display = 'none', 300); // Wait for opacity transition to finish
                }, 3000);
            }
            
        }

        const processStatus = (response, startCameraEnabled = false) => {
            if(response.attendanceStatus == "auto_time_in") {
                if(response.idViolation) {
                    showResponseMessage('🚫 Not Wearing ID', 'danger', false, 'responseMessage2');
                } else {
                    showResponseMessage('✅ Attended!', 'success', false, 'responseMessage2');
                }
            } else if(response.attendanceStatus == "attended_recently" || response.attendanceStatus == "attended_and_timed_out") {
                showResponseMessage('🚫 Attended already!', 'danger', false, 'responseMessage2');
            } else if(response.attendanceStatus == "timeout_updated") {
                showResponseMessage('⏰ Timed Out!', 'warning', false, 'responseMessage2');
            } else if(response.attendanceStatus == "not_attended" && startCameraEnabled) {
                //startCamera();
                return;
            } else if(response.attendanceStatus == "manual_upload_completed") {
                showResponseMessage('📝 Attended (uniform checking is off)!', 'success', false, 'responseMessage2');
            } else if(response.attendanceStatus == "manual_upload_completed_no_id") {
                showResponseMessage('🚫 Not Wearing ID', 'danger', false, 'responseMessage2');
            }

            hideButtons();

            goHome();
        };
    
        fetchStudent();

        preCheckAttendance().then(runCamera => {
            if (runCamera) {
                startCamera();
            }
        });
        
        
    });
</script>