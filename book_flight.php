<?php 
session_start();
include_once 'helpers/helper.php'; 
subview('header.php');
require 'helpers/init_conn_db.php';                      
?> 	
<link href="https://fonts.googleapis.com/css2?family=Assistant:wght@200;400;600&display=swap" rel="stylesheet">
<style>
table {
  background-color: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  border-radius: 8px;
  overflow: hidden;
}
@font-face {
  font-family: 'product sans';
  src: url('assets/css/Product Sans Bold.ttf');
}
h1{
    font-family :'product sans' !important;
    color:#ffffff !important;
    font-size:42px !important;
    margin-top:20px;
    text-align:center;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}
body {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}
th {
  font-size: 18px;
  font-weight: 600;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 15px !important;
}
td {
  padding: 15px !important;
  font-size: 16px;
  font-weight: 500;
  color: #333;
  vertical-align: middle;
}
.container-md {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 30px;
  margin-top: 30px;
  margin-bottom: 80px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
.alert {
  border-radius: 8px;
  padding: 8px 12px;
  font-weight: 600;
  border: none;
}
.alert-primary { background-color: #e3f2fd; color: #1976d2; }
.alert-info { background-color: #e0f7fa; color: #0097a7; }
.alert-danger { background-color: #ffebee; color: #d32f2f; }
.alert-success { background-color: #e8f5e9; color: #388e3c; }
.btn-success {
  background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
  border: none;
  border-radius: 6px;
  padding: 8px 20px;
  font-weight: 600;
  transition: transform 0.3s ease;
}
.btn-success:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.flight-card {
  transition: all 0.3s ease;
}
.flight-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
.search-summary {
  background: white;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 30px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.search-summary h3 {
  color: #667eea;
  font-weight: 600;
}
.footer {
  position: relative;
  margin-top: 50px;
}
@media (max-width: 768px) {
  .container-md {
    margin: 15px;
    padding: 20px;
  }
  h1 {
    font-size: 28px !important;
  }
  th, td {
    font-size: 14px;
    padding: 10px !important;
  }
}
</style>
    <main>
        <?php 
        if(isset($_POST['search_but'])) { 
            $dep_date = $_POST['dep_date'];                        
            $dep_city = $_POST['dep_city'];  
            $arr_city = $_POST['arr_city'];     
            $type = $_POST['type'];
            $f_class = $_POST['f_class'];
            $passengers = $_POST['passengers'];
            
            // Input validation
            if($dep_city === $arr_city){
              header('Location: index.php?error=sameval');
              exit();    
            }
            if($dep_city === '0') {
              header('Location: index.php?error=seldep');
              exit(); 
            }
            if($arr_city === '0') {
              header('Location: index.php?error=selarr');
              exit();              
            }
            if(empty($dep_date)) {
              header('Location: index.php?error=nodate');
              exit();
            }
            
            // Check if date is not in the past
            $current_date = date('Y-m-d');
            if($dep_date < $current_date) {
              header('Location: index.php?error=pastdate');
              exit();
            }
            
            // Check future date limit (up to 1 year from now)
            $max_date = date('Y-m-d', strtotime('+1 year'));
            if($dep_date > $max_date) {
              header('Location: index.php?error=futuredate');
              exit();
            }
            ?>
          <div class="container-md mt-2">
            <div class="search-summary">
              <h3 class="text-center mb-4">
                <i class="fas fa-plane-departure"></i> 
                <?php echo htmlspecialchars($dep_city); ?> 
                <i class="fas fa-long-arrow-alt-right mx-3"></i>
                <i class="fas fa-plane-arrival"></i> 
                <?php echo htmlspecialchars($arr_city); ?>
              </h3>
              <div class="row text-center">
                <div class="col-md-4">
                  <strong><i class="fas fa-calendar-alt"></i> Departure:</strong><br>
                  <?php echo date('F j, Y', strtotime($dep_date)); ?>
                </div>
                <div class="col-md-4">
                  <strong><i class="fas fa-users"></i> Passengers:</strong><br>
                  <?php echo htmlspecialchars($passengers); ?>
                </div>
                <div class="col-md-4">
                  <strong><i class="fas fa-chair"></i> Class:</strong><br>
                  <?php echo ($f_class == 'E') ? 'Economy' : 'Business'; ?>
                </div>
              </div>
            </div>
            
            <h1 class="mb-4">
              Available Flights
            </h1>
            
            <?php
            // Check if any flights found
            $sql = 'SELECT COUNT(*) as count FROM Flight WHERE source=? AND Destination=? AND DATE(departure)=?';
            $stmt = mysqli_stmt_init($conn);
            mysqli_stmt_prepare($stmt,$sql);                
            mysqli_stmt_bind_param($stmt,'sss',$dep_city,$arr_city,$dep_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            
            if($row['count'] == 0) {
              echo '<div class="alert alert-warning text-center">
                      <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                      <h4>No flights found for your selected criteria</h4>
                      <p>Please try different dates or destinations</p>
                      <a href="index.php" class="btn btn-primary mt-2">
                        <i class="fas fa-search"></i> Search Again
                      </a>
                    </div>';
            } else {
            ?>
            <div class="table-responsive">
              <table class="table table-striped table-bordered table-hover">
                <thead>
                  <tr class="text-center">
                    <th scope="col"><i class="fas fa-plane"></i> Airline</th>
                    <th scope="col"><i class="fas fa-clock"></i> Departure</th>
                    <th scope="col"><i class="fas fa-clock"></i> Arrival</th>
                    <th scope="col"><i class="fas fa-info-circle"></i> Status</th>
                    <th scope="col"><i class="fas fa-tag"></i> Fare</th>
                    <th scope="col"><i class="fas fa-shopping-cart"></i> Book</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $sql = 'SELECT * FROM Flight WHERE source=? AND Destination =? AND DATE(departure)=? ORDER BY Price';
                  $stmt = mysqli_stmt_init($conn);
                  mysqli_stmt_prepare($stmt,$sql);                
                  mysqli_stmt_bind_param($stmt,'sss',$dep_city,$arr_city,$dep_date);
                  mysqli_stmt_execute($stmt);
                  $result = mysqli_stmt_get_result($stmt);
                  
                  while ($row = mysqli_fetch_assoc($result)) {
                    $base_price = (int)$row['Price'];
                    $price = $base_price * (int)$passengers;
                    
                    // Calculate price based on type and class
                    if($type === 'round') {
                      $price = $price * 2;
                    }
                    if($f_class == 'B') {
                      $price += $price * 0.5; // 50% more for business
                    }
                    
                    // Format price with thousands separator
                    $formatted_price = number_format($price);
                    
                    // Determine flight status
                    $status = "";
                    $alert = "";
                    $current_time = date('Y-m-d H:i:s');
                    
                    if($row['departure'] > $current_time) {
                      $status = "Scheduled";
                      $alert = 'alert-primary';
                    } else if($row['departure'] <= $current_time && $row['arrivale'] > $current_time) {
                      $status = "In Flight";
                      $alert = 'alert-info';
                    } else if($row['arrivale'] <= $current_time) {
                      $status = "Arrived";
                      $alert = 'alert-success';
                    } else if($row['status'] === 'issue') {
                      $status = "Delayed";
                      $alert = 'alert-danger';
                    }
                    
                    // Calculate duration
                    $dep_time = strtotime($row['departure']);
                    $arr_time = strtotime($row['arrivale']);
                    $duration = $arr_time - $dep_time;
                    $hours = floor($duration / 3600);
                    $minutes = floor(($duration % 3600) / 60);
                    
                    echo "
                    <tr class='text-center flight-card'>                  
                      <td>
                        <strong>".htmlspecialchars($row['airline'])."</strong><br>
                        <small class='text-muted'>Flight #".$row['flight_id']."</small>
                      </td>
                      <td>
                        <strong>".date('h:i A', strtotime($row['departure']))."</strong><br>
                        <small>".date('M j', strtotime($row['departure']))."</small>
                      </td>
                      <td>
                        <strong>".date('h:i A', strtotime($row['arrivale']))."</strong><br>
                        <small>".date('M j', strtotime($row['arrivale']))."</small>
                      </td>
                      <td>
                        <div>
                          <div class='alert ".$alert." text-center mb-0 pt-1 pb-1' role='alert'>
                            <i class='fas fa-plane mr-2'></i>".$status."
                          </div>
                          <small class='text-muted'>Duration: ".$hours."h ".$minutes."m</small>
                        </div>  
                      </td>                   
                      <td>
                        <strong class='text-success'>KES ".$formatted_price."</strong><br>
                        <small>".($type === 'round' ? 'Round Trip' : 'One Way')."</small>
                      </td>";
                    
                    if(isset($_SESSION['userId']) && $status === "Scheduled") {  
                      echo "<td>
                            <form action='pass_form.php' method='post'>
                              <input name='flight_id' type='hidden' value='".$row['flight_id']."'>
                              <input name='type' type='hidden' value='".$type."'>
                              <input name='passengers' type='hidden' value='".$passengers."'>
                              <input name='price' type='hidden' value='".$price."'>
                              <input name='class' type='hidden' value='".$f_class."'>
                              <input name='dep_date' type='hidden' value='".$dep_date."'>
                              <button name='book_but' type='submit' class='btn btn-success'>
                                <i class='fas fa-ticket-alt'></i> Book Now
                              </button>
                            </form>
                            </td>";
                    } elseif (isset($_SESSION['userId']) && $status !== "Scheduled") {
                      echo "<td>
                            <button class='btn btn-secondary' disabled>
                              <i class='fas fa-ban'></i> Not Available
                            </button>
                            </td>";
                    } else {
                      echo "<td>
                            <a href='login.php' class='btn btn-primary'>
                              <i class='fas fa-sign-in-alt'></i> Login to Book
                            </a>
                            </td>";
                    }
                    echo '</tr>';
                  }
                  ?>
                </tbody>
              </table>
            </div>
            <?php } ?>
            
            <div class="text-center mt-4">
              <a href="index.php" class="btn btn-outline-primary">
                <i class="fas fa-search"></i> New Search
              </a>
              <?php if(isset($_SESSION['userId'])) { ?>
                <a href="my_flights.php" class="btn btn-outline-info ml-2">
                  <i class="fas fa-history"></i> My Bookings
                </a>
              <?php } ?>
            </div>
          </div>
        <?php } else { ?>
          <div class="container-md mt-5 text-center">
            <div class="alert alert-warning">
              <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
              <h3>No Search Criteria Provided</h3>
              <p>Please use the search form to find flights</p>
              <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Search
              </a>
            </div>
          </div>
        <?php } ?>
    </main>
    
    <footer class="footer py-4">
      <div class="container">
        <div class="row">
          <div class="col-md-6 text-center text-md-left">
            <em>
              <h5 class="text-light brand mt-2">
                <img src="assets/images/airtic.png" height="50px" width="50px" alt="Airtic Logo">
                <span class="ml-2">AirTic 2026</span>
              </h5>
            </em>
            <p class="text-light mb-0">Your trusted flight booking partner</p>
          </div>
          <div class="col-md-6 text-center text-md-right">
            <p class="text-light mb-0">
              &copy; 2024-2026 AirTic Flight Booking System
            </p>
            <p class="text-light">
              Developed By MD TAJUL ISLAM | v3.0
            </p>
            <div class="mt-2">
              <a href="#" class="text-light mx-2"><i class="fab fa-facebook fa-lg"></i></a>
              <a href="#" class="text-light mx-2"><i class="fab fa-twitter fa-lg"></i></a>
              <a href="#" class="text-light mx-2"><i class="fab fa-instagram fa-lg"></i></a>
            </div>
          </div>
        </div>
      </div>
    </footer>

    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script>
    // Add smooth scrolling and animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add animation to flight cards
        const flightCards = document.querySelectorAll('.flight-card');
        flightCards.forEach((card, index) => {
            card.style.animationDelay = (index * 0.1) + 's';
            card.classList.add('animate__animated', 'animate__fadeInUp');
        });
        
        // Update year in footer dynamically
        document.getElementById('current-year').textContent = new Date().getFullYear();
    });
    </script>
