<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Issue - Bus Tracking System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .error-icon {
            font-size: 64px;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .btn-retry {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 10px 30px;
            border-radius: 5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn-retry:hover {
            color: white;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="error-container text-center">
        <div class="error-icon">
            <i class="fas fa-database"></i>
        </div>
        <h2>Database Connection Issue</h2>
        <p class="text-muted">The system is unable to connect to the database. This is usually temporary.</p>
        
        <div class="alert alert-info text-start mt-4">
            <h5><i class="fas fa-info-circle"></i> Possible Causes:</h5>
            <ul class="mb-0">
                <li>Database server is starting up (wait 1-2 minutes)</li>
                <li>SSL certificate configuration</li>
                <li>Network connectivity issue</li>
                <li>Database credentials need verification</li>
            </ul>
        </div>
        
        <div class="alert alert-success text-start">
            <h5><i class="fas fa-wrench"></i> Try This:</h5>
            <ol class="mb-0">
                <li>Wait 60 seconds and refresh this page</li>
                <li>Check Azure Portal → MySQL Server is running</li>
                <li>Verify environment variables in App Service Configuration</li>
                <li>Check deployment logs in Azure Portal</li>
            </ol>
        </div>
        
        <a href="javascript:location.reload();" class="btn-retry">
            <i class="fas fa-sync-alt"></i> Retry Connection
        </a>
        <br>
        <a href="test_connection.php" class="btn btn-link mt-2">
            <i class="fas fa-stethoscope"></i> Run Diagnostic Test
        </a>
    </div>
    
    <script>
        // Auto-retry after 60 seconds
        setTimeout(function() {
            console.log('Auto-retrying connection...');
            location.reload();
        }, 60000);
    </script>
</body>
</html>
