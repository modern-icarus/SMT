<?php include '../header.html';?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select User</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head>
    <style>
      canvas {
        max-width: 100%;
        display: block;
        margin: 10px 0;
      }
      button {
        margin-top: 10px;
      }
    </style>
<body>
  <h1>Capture Photo</h1>
    <canvas id="canvas"></canvas>
    <button id="startCamera">Start Camera</button>
    <button id="capture" disabled>Capture Photo</button>

    <script>
      let stream; // To store the camera stream

      const canvas = document.getElementById('canvas');
      const startCameraButton = document.getElementById('startCamera');
      const captureButton = document.getElementById('capture');

      // Start the camera
      async function startCamera() {
        try {
          stream = await navigator.mediaDevices.getUserMedia({ video: true });
          const videoTrack = stream.getVideoTracks()[0];

          // Create an image capture
          const imageCapture = new ImageCapture(videoTrack);

          captureButton.disabled = false;

          // Capture photo on button click
          captureButton.onclick = async () => {
            const photo = await imageCapture.takePhoto();
            const img = new Image();
            img.src = URL.createObjectURL(photo);
            img.onload = () => {
              // Draw the photo on canvas
              const context = canvas.getContext('2d');
              canvas.width = img.width;
              canvas.height = img.height;
              context.drawImage(img, 0, 0);
              URL.revokeObjectURL(img.src); // Clean up
            };

            // Stop the camera stream
            stopCamera();
          };
        } catch (error) {
          console.error('Error accessing the camera:', error);
          alert('Unable to access the camera. Please allow camera access.');
        }
      }

      // Stop the camera
      function stopCamera() {
        if (stream) {
          stream.getTracks().forEach(track => track.stop());
          captureButton.disabled = true; // Disable the capture button after stopping the camera
        }
      }

      startCameraButton.addEventListener('click', startCamera);
    </script>

    <!-- <script src="js/main.js"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>