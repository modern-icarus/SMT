<script type="text/javascript">
    $(document).ready(function(){
        $('#closeBtn').click(() => {
            window.location.href = "../";
        });

        // Single function to fetch all student data
        function fetchStudentData() {
            $.ajax({
                url: '../php-api/ReadStudentRecord.php', 
                method: 'GET',
                data: { StudentID: <?php echo $_SESSION['student']['StudentID']; ?> },
                success: function(response) {
                    if (response.status === 'success') {
                        var student = response.student;

                        // Populate student info
                        $('#studentNoDisplay').text(student.StudentID);
                        $('#studentNameDisplay').text(student.StudentName);
                        $('#studentYearDisplay').text(student.Year);
                        $('#studentCourseDisplay').text(student.ProgramCode);
                        
                        // Populate yearly record data
                        $('#studentTotalViolations').text(response.violationCount);
                        $('#studentTotalPendingViolations').text(response.totalPendingViolations);
                        $('#studentTotalAttendance').text(response.totalAttendance);
                        
                    } else {
                        alert('Failed to fetch student details: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error fetching student details.');
                }
            });
        }

        // Initialize data fetching
        fetchStudentData();
    });
</script>