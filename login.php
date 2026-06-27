<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "remsresto_db";
$errorMessage = "";
$isAuthenticated = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $submittedPin = $_POST['pin'] ?? '';

    $mysqli = new mysqli($host, $user, $pass, $dbname);
    if ($mysqli->connect_errno) {
        $errorMessage = "Database connection failed: " . $mysqli->connect_error;
    } else {
        // Check user_pins table first
        $stmt = $mysqli->prepare("SELECT role FROM user_pins WHERE pin = ?");
        $stmt->bind_param("s", $submittedPin);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $pinData = $result->fetch_assoc();
            $_SESSION['user_type'] = $pinData['role'] === 'inventory' ? 'admin' : 'cashier';
            $isAuthenticated = true;
            
            // Redirect based on role
            if ($pinData['role'] === 'inventory') {
                header("Location: inventory.php");
            } else {
                header("Location: home.php");
            }
            exit();
        } else {
            // Fallback to old users table if needed
            $stmt = $mysqli->prepare("SELECT id, user_type FROM users WHERE pin = ?");
            $stmt->bind_param("s", $submittedPin);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['user_id'] = $user['id'];
                $isAuthenticated = true;
                
                if ($user['user_type'] === 'admin') {
                    header("Location: inventory.php");
                } else {
                    header("Location: home.php");
                }
                exit();
            } else {
                $errorMessage = "Incorrect PIN. Please try again.";
            }
        }
        $stmt->close();
        $mysqli->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>REMS RESTO - Welcome</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        
        :root {
            --primary-gradient: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            --primary-dark: #b91c1c;
            --primary-light: #f87171;
            --secondary-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --accent-gradient: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
        }
        
        body { 
            font-family: 'Inter', sans-serif;
            background: linear-gradient(rgba(0,0,0,0.8), rgba(0,0,0,0.8)), 
                        url('images/2be0107a-02ff-48e0-92c4-f109ec040290.png') center/cover fixed no-repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 25px 50px -12px rgba(220, 38, 38, 0.25);
        }
        
        .logo-container {
            background: var(--primary-gradient);
            border-radius: 20px;
            padding: 3px;
            box-shadow: 0 10px 25px rgba(220, 38, 38, 0.3);
        }
        
        .logo-image {
            border-radius: 18px;
            border: 3px solid white;
            box-shadow: inset 0 0 20px rgba(255, 255, 255, 0.3);
        }
        
        .pin-dot { 
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
        }
        
        @media (min-width: 768px) {
            .pin-dot {
                width: 20px;
                height: 20px;
            }
        }
        
        .pin-dot.filled { 
            background: var(--primary-gradient);
            transform: scale(1.3);
            border-color: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
        }
        
        .pin-button {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            background: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .pin-button:hover { 
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(220, 38, 38, 0.2);
            border-color: var(--primary-dark);
            background: #fef2f2;
        }
        
        .pin-button:active { 
            transform: scale(0.95);
        }
        
        .submit-btn {
            background: var(--primary-gradient);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .submit-btn:hover {
            background: var(--secondary-gradient);
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(220, 38, 38, 0.3);
        }
        
        .clear-btn {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #dc2626;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .clear-btn:hover {
            background: linear-gradient(135deg, #fca5a5 0%, #f87171 100%);
            transform: translateY(-2px);
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes pulse-glow {
            from { box-shadow: 0 0 20px rgba(220, 38, 38, 0.4); }
            to { box-shadow: 0 0 30px rgba(220, 38, 38, 0.8); }
        }
        
        .demo-badge {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }
        
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-3px, 0, 0); }
            40%, 60% { transform: translate3d(3px, 0, 0); }
        }
        
        /* Responsive adjustments */
        @media (max-width: 640px) {
            .container {
                padding: 1rem;
                margin: 0.5rem;
            }
            
            .logo-container {
                width: 5rem;
                height: 5rem;
            }
            
            .keypad-grid {
                gap: 0.5rem;
            }
            
            .pin-button {
                padding: 0.75rem;
            }
        }
        
        @media (max-width: 400px) {
            .container {
                border-radius: 1rem;
                padding: 1.5rem;
            }
            
            .pin-dot {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="glass-effect rounded-3xl shadow-2xl p-6 sm:p-8 w-full max-w-md relative z-10 transform hover:scale-[1.02] transition-transform duration-500">
        
        <!-- Header with Logo -->
        <div class="text-center mb-8">
            <div class="logo-container w-24 h-24 mx-auto mb-4 flex items-center justify-center shadow-lg floating">
                <img src="images/2be0107a-02ff-48e0-92c4-f109ec040290.png" alt="REMS RESTO Logo" class="logo-image w-full h-full object-cover">
            </div>
            <h1 class="text-4xl font-extrabold bg-gradient-to-r from-red-600 to-red-800 bg-clip-text text-transparent mb-2">
                REM'S RESTO
            </h1>
            <p class="text-gray-600 text-lg font-medium"></p>
            <p class="text-gray-500 text-sm mt-1">Please enter your PIN to continue</p>
        </div>

        <form method="POST" action="" class="space-y-6">
            <!-- PIN Dots -->
            <div class="flex justify-center space-x-6 mb-8">
                <div class="pin-dot" id="dot1"></div>
                <div class="pin-dot" id="dot2"></div>
                <div class="pin-dot" id="dot3"></div>
                <div class="pin-dot" id="dot4"></div>
            </div>

            <!-- Keypad -->
            <div class="grid grid-cols-3 gap-3 mb-6">
                <?php for ($i = 1; $i <= 9; $i++): ?>
                    <button type="button"
                            class="pin-button text-xl font-bold py-4 rounded-xl text-gray-700 hover:text-red-600"
                            onclick="addPin('<?php echo $i; ?>')">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button type="button"
                        class="pin-button clear-btn py-4 rounded-xl font-bold"
                        onclick="clearPin()">
                    <i class="fas fa-backspace text-lg"></i>
                </button>
                <button type="button"
                        class="pin-button text-xl font-bold py-4 rounded-xl text-gray-700 hover:text-red-600"
                        onclick="addPin('0')">0
                </button>
                <button type="submit"
                        class="pin-button submit-btn py-4 rounded-xl font-bold shadow-lg">
                    <i class="fas fa-arrow-right mr-2"></i>Enter
                </button>
            </div>

            <!-- Error Message -->
            <?php if ($errorMessage): ?>
                <div class="error-message text-center font-medium py-3 px-4 rounded-xl mb-4 animate-pulse">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

           
        </form>

    </div>

    <script>
        let pin = "";

        function addPin(num) {
            if (pin.length < 4) {
                pin += num;
                updateDots();
                animateDot(pin.length);
            }
        }

        function clearPin() {
            pin = "";
            updateDots();
        }

        function updateDots() {
            for (let i = 1; i <= 4; i++) {
                const dot = document.getElementById("dot" + i);
                if (i <= pin.length) {
                    dot.classList.add("filled");
                } else {
                    dot.classList.remove("filled");
                }
            }
        }

        function animateDot(index) {
            const dot = document.getElementById("dot" + index);
            dot.style.animation = 'none';
            setTimeout(() => {
                dot.style.animation = 'pulse 0.5s ease-in-out';
            }, 10);
        }

        document.querySelector('form').addEventListener('submit', function (e) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'pin';
            input.value = pin;
            this.appendChild(input);
        });

        // Add keyboard support
        document.addEventListener('keydown', function(e) {
            if (e.key >= '0' && e.key <= '9') {
                addPin(e.key);
            } else if (e.key === 'Backspace') {
                clearPin();
            } else if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });

        // Add floating animation style
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>