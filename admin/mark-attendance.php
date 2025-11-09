<?php
session_start();
$userID = $_SESSION['employee_id'] ?? null;
if (!$userID) {
    echo json_encode(['success' => false, 'errors' => ['Unauthorized request. Please log in.']]);
    exit;
}

include_once 'includes/auth.php';
include_once 'includes/dbconnection.php';

$employee_id = $_SESSION['employee_id'];
$today = date('Y-m-d');

// Check clock in status
$stmtIn = $dbh->prepare("SELECT * FROM clock_ins WHERE employee_id = ? AND DATE(clock_in_time) = ?");
$stmtIn->execute([$employee_id, $today]);
$hasClockedIn = $stmtIn->rowCount() > 0;

// Check clock out status
$stmtOut = $dbh->prepare("SELECT * FROM clock_outs WHERE employee_id = ? AND date = ?");
$stmtOut->execute([$employee_id, $today]);
$hasClockedOut = $stmtOut->rowCount() > 0;
?>
<?php include_once 'includes/sidebar.php'; ?>
<?php include_once 'includes/header.php'; ?>
<?php include_once 'includes/app-bar.php'; ?>
<main class="flex-1 md:ml-64 px-4 transition-all">
    <div class="flex flex-col md:flex-row md:justify-between md:items-center bg-white shadow-md rounded-md p-6 mt-24 md:mt-32 gap-10">
    <div class="space-y-2 w-full md:w-1/2">
        <div class="flex flex-col">
            <small class="text-md font-bold uppercase">Time You Got To Work</small>
            <small class="text-md text-red-500 font-bold animate-bounce">NB: You must allow your GPS location</small>
        </div>
        <form id="clockInForm">
            <input type="hidden" name="lat" id="lat-in">
            <input type="hidden" name="lng" id="lng-in">
           <button id="clockInBtn" type="button"
    class="bg-green-600 text-white px-4 py-2 rounded disabled:bg-gray-300 disabled:cursor-not-allowed"
    <?php if ($hasClockedIn): ?>disabled<?php endif; ?>>
    Clock In
</button>
        </form>
    </div>

    <div class="space-y-2 w-full md:w-1/2">
        <div class="flex flex-col">
            <small class="text-md font-bold uppercase">Time You Left Work</small>
            <small class="text-md text-red-500 font-bold animate-bounce">NB: You must allow your GPS location</small>
        </div>
        <form id="clockOutForm">
            <input type="hidden" name="lat" id="lat-out">
            <input type="hidden" name="lng" id="lng-out">
           <button id="clockOutBtn" type="button"
    class="bg-red-600 text-white px-4 py-2 rounded disabled:bg-gray-300 disabled:cursor-not-allowed"
    <?php if (!$hasClockedIn || $hasClockedOut): ?>disabled<?php endif; ?>>
    Clock Out
</button>
        </form>
    </div>
</div>
</main>
<?php include_once 'includes/footer.php'; ?>
<script>
const hasClockedIn = <?= json_encode($hasClockedIn) ?>;
    const hasClockedOut = <?= json_encode($hasClockedOut) ?>;

    const clockInBtn = document.getElementById('clockInBtn');
    const clockOutBtn = document.getElementById('clockOutBtn');

    // Button state logic
    if (!hasClockedIn) {
        clockInBtn.disabled = false;
        clockOutBtn.disabled = true;
    } else if (hasClockedIn && !hasClockedOut) {
        clockInBtn.disabled = true;
        clockOutBtn.disabled = false;
    } else if (hasClockedIn && hasClockedOut) {
        clockInBtn.disabled = false;
        clockOutBtn.disabled = true;
    }

    // Clock In
    clockInBtn.addEventListener('click', async function () {
        navigator.geolocation.getCurrentPosition(async function(position) {
            document.getElementById('lat-in').value = position.coords.latitude;
            document.getElementById('lng-in').value = position.coords.longitude;

            const form = document.getElementById('clockInForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('./functions/employee/clock-in.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const result = await res.json();

                if (result.success) {
                    clockInBtn.disabled = true;
                    clockOutBtn.disabled = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Clocked In',
                        text: 'Thank you for reporting to work!',
                        timer: 2500,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Clock-in failed.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server.'
                });
            }
        }, function() {
            Swal.fire({
                icon: 'warning',
                title: 'Location Required',
                text: 'Please allow location access to clock in.'
            });
        });
    });

    // Clock Out
    clockOutBtn.addEventListener('click', async function () {
        navigator.geolocation.getCurrentPosition(async function(position) {
            document.getElementById('lat-out').value = position.coords.latitude;
            document.getElementById('lng-out').value = position.coords.longitude;

            const form = document.getElementById('clockOutForm');
            const formData = new FormData(form);

            try {
                const res = await fetch('./functions/employee/clock-out.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'include'
                });
                const result = await res.json();

                if (result.success) {
                    clockOutBtn.disabled = true;
                    clockInBtn.disabled = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Clocked Out',
                        text: 'Great work! Have a safe trip home.',
                        timer: 2500,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: result.message || 'Clock-out failed.'
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Network Error',
                    text: 'Could not reach the server.'
                });
            }
        }, function() {
            Swal.fire({
                icon: 'warning',
                title: 'Location Required',
                text: 'Please allow location access to clock out.'
            });
        });
    });
</script>
</body>
</html>
