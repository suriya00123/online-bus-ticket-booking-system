<?php
include '../config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
$uid = $_SESSION['user_id'];
$total_bookings_q = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=?"); $total_bookings_q->execute([$uid]); $total_bookings = $total_bookings_q->fetchColumn();
$total_spent_q    = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE user_id=? AND payment_status='paid'"); $total_spent_q->execute([$uid]); $total_spent = $total_spent_q->fetchColumn();
$pending_q        = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id=? AND payment_status='pending'"); $pending_q->execute([$uid]); $pending_count = $pending_q->fetchColumn();
$stmt = $pdo->prepare("SELECT b.pnr,bu.bus_name,bu.from_city,bu.to_city,bu.travel_date,b.seat_numbers,b.total_amount,b.booking_date,b.payment_status FROM bookings b JOIN buses bu ON b.bus_id=bu.id WHERE b.user_id=? ORDER BY b.booking_date DESC LIMIT 6");
$stmt->execute([$uid]); $bookings = $stmt->fetchAll();
$stmt2 = $pdo->prepare("SELECT b.pnr,bu.bus_name,bu.from_city,bu.to_city,bu.travel_date,b.seat_numbers,b.total_amount,b.booking_date,b.payment_status,b.razorpay_payment_id FROM bookings b JOIN buses bu ON b.bus_id=bu.id WHERE b.user_id=? ORDER BY b.booking_date DESC");
$stmt2->execute([$uid]); $all_bookings = $stmt2->fetchAll();
$stmt3 = $pdo->prepare("SELECT * FROM users WHERE id=?"); $stmt3->execute([$uid]); $user = $stmt3->fetch();
$buses_list = $pdo->query("SELECT b.*,COUNT(CASE WHEN s.status='available' THEN 1 END) AS avail_seats FROM buses b LEFT JOIN seats s ON b.id=s.bus_id GROUP BY b.id ORDER BY b.travel_date ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>BusGo — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
:root{--bg:#0a0f1e;--surface:#111827;--card:#1a2235;--border:#1f2d45;--accent:#00d4aa;--accent2:#6c63ff;--red:#ff4d6d;--yellow:#fbbf24;--text:#e2e8f0;--muted:#64748b;--sw:260px}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh}
/* SIDEBAR */
.sidebar{width:var(--sw);background:var(--surface);position:fixed;top:0;left:0;height:100vh;display:flex;flex-direction:column;border-right:1px solid var(--border);z-index:100}
.logo{padding:26px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--accent),var(--accent2));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0}
.logo h2{font-family:'Syne',sans-serif;font-size:21px;font-weight:800;background:linear-gradient(135deg,var(--accent),var(--accent2));-webkit-background-clip:text;-webkit-text-fill-color:transparent}
.logo small{font-size:11px;color:var(--muted);display:block;margin-top:1px}
.user-box{padding:18px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.av{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--accent));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:17px;color:#fff;flex-shrink:0}
.av-name{font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.av-role{font-size:11px;color:var(--accent);background:rgba(0,212,170,.12);padding:2px 10px;border-radius:20px;display:inline-block;margin-top:4px}
nav{flex:1;padding:14px 0;overflow-y:auto}
.ni{display:flex;align-items:center;gap:13px;padding:13px 22px;cursor:pointer;color:var(--muted);font-size:14px;font-weight:500;border-left:3px solid transparent;transition:.2s}
.ni:hover{color:var(--text);background:rgba(255,255,255,.03)}
.ni.active{color:var(--accent);background:rgba(0,212,170,.07);border-left-color:var(--accent)}
.ni i{width:17px;text-align:center}
.sf{padding:18px 22px;border-top:1px solid var(--border)}
.lg-btn{display:flex;align-items:center;gap:10px;color:var(--red);font-size:14px;text-decoration:none;padding:10px 14px;border-radius:10px;transition:.2s}
.lg-btn:hover{background:rgba(255,77,109,.1)}
/* MAIN */
.main{margin-left:var(--sw);flex:1;padding:30px;background:var(--bg)}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
.pg-title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800}
.pg-title span{color:var(--accent)}
.live-pill{background:var(--card);border:1px solid var(--border);padding:7px 16px;border-radius:30px;font-size:12px;color:var(--muted);display:flex;align-items:center;gap:7px}
.dot{width:7px;height:7px;background:var(--accent);border-radius:50%;animation:pulse 1.5s infinite}
@keyframes pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.5);opacity:.5}}
/* SECTIONS */
.sec{display:none;animation:fu .35s ease}
.sec.active{display:block}
@keyframes fu{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
/* STATS */
.sg{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:18px;margin-bottom:28px}
.sc{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:22px;position:relative;overflow:hidden;transition:.3s;cursor:default}
.sc:hover{transform:translateY(-3px)}
.sc::before{content:'';position:absolute;top:-25px;right:-25px;width:80px;height:80px;border-radius:50%;opacity:.08}
.sc.g::before{background:var(--accent)}.sc.p::before{background:var(--accent2)}.sc.y::before{background:var(--yellow)}.sc.r::before{background:var(--red)}
.si{width:42px;height:42px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:14px}
.sc.g .si{background:rgba(0,212,170,.12);color:var(--accent)}.sc.p .si{background:rgba(108,99,255,.12);color:var(--accent2)}.sc.y .si{background:rgba(251,191,36,.12);color:var(--yellow)}.sc.r .si{background:rgba(255,77,109,.12);color:var(--red)}
.sv{font-family:'Syne',sans-serif;font-size:26px;font-weight:800;margin-bottom:3px}
.sl{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px}
/* BOOKING CARDS */
.bg{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:14px}
.bc{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:18px;transition:.3s;position:relative;overflow:hidden}
.bc::after{content:'';position:absolute;bottom:0;left:0;height:3px;width:100%}
.bc.paid::after{background:linear-gradient(90deg,var(--accent),var(--accent2))}.bc.pending::after{background:var(--yellow)}.bc.failed::after{background:var(--red)}
.bc:hover{transform:translateY(-3px)}
.bch{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px}
.pnr{font-family:'Syne',sans-serif;font-size:12px;color:var(--accent);font-weight:700;letter-spacing:1px}
.badge{padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.badge.paid{background:rgba(0,212,170,.12);color:var(--accent)}.badge.pending{background:rgba(251,191,36,.12);color:var(--yellow)}.badge.failed{background:rgba(255,77,109,.12);color:var(--red)}
.brt{display:flex;align-items:center;gap:8px;margin-bottom:10px}
.city{font-family:'Syne',sans-serif;font-size:15px;font-weight:700}
.rl{flex:1;height:1px;background:var(--border);position:relative}
.rl::after{content:'🚌';position:absolute;top:-9px;left:50%;transform:translateX(-50%);font-size:11px}
.bmeta{display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:11px;color:var(--muted)}
.bmeta strong{color:var(--text);display:block;font-size:12px}
.bamt{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;color:var(--accent);margin-top:10px}
/* SEARCH */
.sp{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:26px;margin-bottom:24px}
.sf2{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;align-items:end}
.fw label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px}
.fw input{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);padding:11px 14px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;transition:.2s;outline:none}
.fw input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(0,212,170,.1)}
.sbtn{background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff;padding:12px 26px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;white-space:nowrap;transition:.2s}
.sbtn:hover{opacity:.85;transform:translateY(-1px)}
/* BUS RESULT */
.brc{background:var(--bg);border:1px solid var(--border);border-radius:14px;padding:18px;display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;transition:.3s;cursor:pointer}
.brc:hover,.brc.sel{border-color:var(--accent);background:rgba(0,212,170,.04)}
.bn{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:5px}
.bm{font-size:12px;color:var(--muted)}
.ba-right{text-align:right}
.ac{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;color:var(--accent)}
.al{font-size:11px;color:var(--muted)}
.bp{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-top:3px}
.selbtn{background:var(--accent);border:none;color:#0a0f1e;padding:8px 18px;border-radius:8px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;font-size:12px;margin-top:6px;transition:.2s}
.selbtn:hover{opacity:.85}
/* SEAT MAP */
.seatp{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:26px;margin-top:22px}
.seat-leg{display:flex;gap:18px;margin-bottom:18px;flex-wrap:wrap}
.li{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--muted)}
.ld{width:14px;height:14px;border-radius:4px}
.ld.available{background:rgba(0,212,170,.15);border:2px solid var(--accent)}
.ld.booked   {background:var(--border);border:2px solid var(--border)}
.ld.selected {background:var(--accent2);border:2px solid var(--accent2)}
.bus-shell{background:var(--bg);border:2px solid var(--border);border-radius:18px;padding:22px}
.bus-front-label{text-align:center;margin-bottom:18px;padding-bottom:14px;border-bottom:1px dashed var(--border);font-size:11px;color:var(--muted);letter-spacing:2px;text-transform:uppercase}
.sgrid{display:grid;grid-template-columns:repeat(5,48px);gap:8px;justify-content:center}
.seat{width:48px;height:48px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;cursor:pointer;transition:all .18s;border:2px solid transparent;font-family:'Syne',sans-serif}
.seat.available{background:rgba(0,212,170,.1);border-color:var(--accent);color:var(--accent)}
.seat.available:hover{background:var(--accent);color:#0a0f1e;transform:scale(1.1)}
.seat.booked   {background:var(--border);color:var(--muted);cursor:not-allowed}
.seat.selected {background:var(--accent2);border-color:var(--accent2);color:#fff;transform:scale(1.1)}
.seat.aisle    {background:transparent;border:none;cursor:default}
.bsum{background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:18px;margin-top:18px}
.sr{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid var(--border)}
.sr:last-child{border:none}
.sr .lbl{font-size:12px;color:var(--muted)}
.sr .val{font-family:'Syne',sans-serif;font-weight:700}
.sr.tot .val{color:var(--accent);font-size:20px}
.pbtn{width:100%;background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff;padding:14px;border-radius:11px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;margin-top:14px;transition:.2s}
.pbtn:hover{opacity:.85;transform:translateY(-2px)}
.pbtn:disabled{opacity:.35;cursor:not-allowed;transform:none}
/* TABLE */
.tw{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;overflow-x:auto}
.tbl{width:100%;border-collapse:collapse;min-width:600px}
.tbl thead tr{background:rgba(255,255,255,.02)}
.tbl th{padding:13px 16px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:600;border-bottom:1px solid var(--border)}
.tbl td{padding:13px 16px;font-size:13px;border-bottom:1px solid rgba(31,45,69,.4);vertical-align:middle}
.tbl tbody tr:hover{background:rgba(255,255,255,.02)}
.tbl tbody tr:last-child td{border:none}
/* AVAIL BAR */
.abar{height:5px;background:var(--border);border-radius:3px;margin-top:4px;overflow:hidden;width:70px}
.afill{height:100%;border-radius:3px;transition:.3s}
/* PROFILE */
.pcard{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:30px;max-width:540px}
.phero{display:flex;align-items:center;gap:18px;margin-bottom:24px;padding-bottom:24px;border-bottom:1px solid var(--border)}
.pav{width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--accent));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:26px;color:#fff}
.pname{font-family:'Syne',sans-serif;font-size:20px;font-weight:800}
.pemail{font-size:13px;color:var(--muted);margin-top:3px}
.pf{margin-bottom:16px}
.pf label{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px}
.pf input{width:100%;background:var(--bg);border:1px solid var(--border);color:var(--text);padding:11px 14px;border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:.2s}
.pf input:focus{border-color:var(--accent)}
.pf input[readonly]{opacity:.5;cursor:default}
.savbtn{background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff;padding:12px 30px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif}
/* PAYMENT OVERLAY */
.ov{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.75);z-index:999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(8px)}
.ov.show{display:flex}
.pm{background:var(--surface);border:1px solid var(--border);border-radius:22px;padding:36px;max-width:420px;width:90%;text-align:center;animation:fu .3s ease}
.pm h2{font-family:'Syne',sans-serif;font-size:21px;font-weight:800;margin-bottom:6px}
.pm p{color:var(--muted);font-size:13px;margin-bottom:24px}
.pamount{font-family:'Syne',sans-serif;font-size:46px;font-weight:800;color:var(--accent);margin:16px 0}
.paybtn{background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff;padding:14px 36px;border-radius:11px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;margin:6px;transition:.2s}
.paybtn:hover{opacity:.85}
.canbtn{background:rgba(255,255,255,.05);border:1px solid var(--border);color:var(--muted);padding:14px 36px;border-radius:11px;font-size:14px;cursor:pointer;margin:6px}
/* EMPTY */
.empty{text-align:center;padding:50px 20px;color:var(--muted)}
.empty i{font-size:44px;margin-bottom:14px;opacity:.25;display:block}
/* LOADING */
.ldg{text-align:center;padding:36px;color:var(--muted)}
.spin{display:inline-block;width:30px;height:30px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .8s linear infinite;margin-bottom:10px}
@keyframes spin{to{transform:rotate(360deg)}}
/* TOAST */
.toast{position:fixed;bottom:22px;right:22px;background:var(--card);border:1px solid var(--accent);border-radius:12px;padding:14px 22px;font-size:13px;font-weight:500;z-index:9999;transform:translateY(80px);opacity:0;transition:.4s}
.toast.show{transform:none;opacity:1}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}
.sec-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:700}
.sec-title span{color:var(--accent)}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon">🚌</div>
    <div><h2>BusGo</h2><small>Ticket Platform</small></div>
  </div>
  <div class="user-box">
    <div class="av"><?php echo strtoupper(substr($_SESSION['username'],0,1)); ?></div>
    <div style="overflow:hidden">
      <div class="av-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
      <span class="av-role">Passenger</span>
    </div>
  </div>
  <nav>
    <div class="ni active" onclick="show('dashboard',this)"><i class="fas fa-th-large"></i> Dashboard</div>
    <div class="ni" onclick="show('search',this)"><i class="fas fa-search"></i> Search & Book</div>
    <div class="ni" onclick="show('buses',this)"><i class="fas fa-bus"></i> View Buses</div>
    <div class="ni" onclick="show('bookings',this)"><i class="fas fa-ticket-alt"></i> My Bookings</div>
    <div class="ni" onclick="show('profile',this)"><i class="fas fa-user-circle"></i> My Profile</div>
  </nav>
  <div class="sf">
    <a href="../logout.php" class="lg-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>
</aside>

<main class="main">
  <div class="topbar">
    <div class="pg-title" id="pgTitle">Dashboard <span>Overview</span></div>
    <div class="live-pill"><div class="dot"></div> Live System</div>
  </div>

  <!-- DASHBOARD -->
  <section id="dashboard" class="sec active">
    <div class="sg">
      <div class="sc g"><div class="si"><i class="fas fa-ticket-alt"></i></div><div class="sv"><?php echo $total_bookings; ?></div><div class="sl">Total Bookings</div></div>
      <div class="sc p"><div class="si"><i class="fas fa-rupee-sign"></i></div><div class="sv">₹<?php echo number_format($total_spent,0); ?></div><div class="sl">Amount Spent</div></div>
      <div class="sc y"><div class="si"><i class="fas fa-clock"></i></div><div class="sv"><?php echo $pending_count; ?></div><div class="sl">Pending Payments</div></div>
      <div class="sc r"><div class="si"><i class="fas fa-bus"></i></div><div class="sv"><?php echo count($buses_list); ?></div><div class="sl">Available Buses</div></div>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <div class="sec-title">Recent <span>Bookings</span></div>
      <span style="font-size:12px;color:var(--muted);cursor:pointer" onclick="show('bookings',document.querySelectorAll('.ni')[3])">View all →</span>
    </div>
    <?php if(empty($bookings)): ?>
    <div class="empty"><i class="fas fa-ticket-alt"></i><p>No bookings yet. <strong style="color:var(--accent);cursor:pointer" onclick="show('search',document.querySelectorAll('.ni')[1])">Book your first ticket →</strong></p></div>
    <?php else: ?>
    <div class="bg">
    <?php foreach($bookings as $b): ?>
      <div class="bc <?php echo $b['payment_status']; ?>">
        <div class="bch"><div class="pnr"><?php echo $b['pnr']; ?></div><span class="badge <?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></div>
        <div class="brt"><div class="city"><?php echo $b['from_city']; ?></div><div class="rl"></div><div class="city"><?php echo $b['to_city']; ?></div></div>
        <div class="bmeta">
          <div><strong><?php echo $b['bus_name']; ?></strong>Bus</div>
          <div><strong><?php echo date('d M Y',strtotime($b['travel_date'])); ?></strong>Travel Date</div>
          <div><strong><?php echo $b['seat_numbers']; ?></strong>Seats</div>
          <div><strong><?php echo date('d M',strtotime($b['booking_date'])); ?></strong>Booked</div>
        </div>
        <div class="bamt">₹<?php echo number_format($b['total_amount'],2); ?></div>
      </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>

  <!-- SEARCH & BOOK -->
  <section id="search" class="sec">
    <div class="sp">
      <div class="sec-title" style="margin-bottom:18px">Search <span>Buses</span></div>
      <div class="sf2">
        <div class="fw"><label>From City</label><input type="text" id="from" placeholder="Chennai" value="Chennai"></div>
        <div class="fw"><label>To City</label>  <input type="text" id="to"   placeholder="Bangalore" value="Bangalore"></div>
        <div class="fw"><label>Travel Date</label><input type="date" id="date" value="2026-03-12"></div>
        <button class="sbtn" onclick="searchBuses()"><i class="fas fa-search"></i> Search</button>
      </div>
    </div>
    <div id="busResults"></div>
    <div id="seatPanel" class="seatp" style="display:none">
      <div class="sec-title" style="margin-bottom:14px">Select <span>Seats</span> — <span id="busNameLbl" style="color:var(--muted);font-size:13px;font-weight:400"></span></div>
      <div class="seat-leg">
        <div class="li"><div class="ld available"></div> Available</div>
        <div class="li"><div class="ld booked"></div>    Booked</div>
        <div class="li"><div class="ld selected"></div>  Selected</div>
      </div>
      <div class="bus-shell">
        <div class="bus-front-label">🚌 — DRIVER — FRONT OF BUS</div>
        <div class="sgrid" id="seatGrid"></div>
      </div>
      <div class="bsum">
        <div class="sr"><span class="lbl">Selected Seats</span><span class="val" id="selSeats">None</span></div>
        <div class="sr"><span class="lbl">Price / Seat</span><span class="val" id="priceLabel">₹0</span></div>
        <div class="sr tot"><span class="lbl">Total Amount</span><span class="val" id="totalLabel">₹0</span></div>
      </div>
      <button class="pbtn" id="proceedBtn" onclick="proceedToPayment()" disabled>💳 Proceed to Payment</button>
    </div>
  </section>

  <!-- VIEW BUSES -->
  <section id="buses" class="sec">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
      <div class="sec-title">All <span>Buses</span></div>
      <div class="live-pill"><div class="dot"></div> Live Availability</div>
    </div>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>Bus Name</th><th>Route</th><th>Date</th><th>Price/Seat</th><th>Seat Availability</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($buses_list as $bus):
          $pct = $bus['total_seats']>0 ? round(($bus['avail_seats']/$bus['total_seats'])*100) : 0;
          $col = $pct>50 ? 'var(--accent)' : ($pct>20 ? 'var(--yellow)' : 'var(--red)');
        ?>
        <tr>
          <td><strong><?php echo $bus['bus_name']; ?></strong></td>
          <td><?php echo $bus['from_city']; ?> → <?php echo $bus['to_city']; ?></td>
          <td><?php echo date('d M Y',strtotime($bus['travel_date'])); ?></td>
          <td><strong style="color:var(--accent)">₹<?php echo $bus['price_per_seat']; ?></strong></td>
          <td>
            <div style="font-size:13px;color:<?php echo $col; ?>;font-weight:700"><?php echo $bus['avail_seats']; ?> / <?php echo $bus['total_seats']; ?> seats</div>
            <div class="abar"><div class="afill" style="width:<?php echo $pct; ?>%;background:<?php echo $col; ?>"></div></div>
          </td>
          <td><button onclick="quickBook(<?php echo $bus['id']; ?>,'<?php echo addslashes($bus['bus_name']); ?>','<?php echo $bus['from_city']; ?>','<?php echo $bus['to_city']; ?>',<?php echo $bus['price_per_seat']; ?>,<?php echo $bus['total_seats']; ?>)" style="background:var(--accent);border:none;color:#0a0f1e;padding:7px 16px;border-radius:8px;font-weight:700;cursor:pointer;font-family:'Syne',sans-serif;font-size:12px;transition:.2s">Book →</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- MY BOOKINGS -->
  <section id="bookings" class="sec">
    <div class="sec-title" style="margin-bottom:18px">My <span>Bookings</span></div>
    <?php if(empty($all_bookings)): ?>
    <div class="empty"><i class="fas fa-ticket-alt"></i><p>No bookings yet.</p></div>
    <?php else: ?>
    <div class="tw">
      <table class="tbl">
        <thead><tr><th>PNR</th><th>Bus</th><th>Route</th><th>Date</th><th>Seats</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($all_bookings as $b): ?>
        <tr>
          <td><span style="font-family:'Syne',sans-serif;color:var(--accent);font-weight:700;font-size:11px;letter-spacing:1px"><?php echo $b['pnr']; ?></span></td>
          <td><?php echo $b['bus_name']; ?></td>
          <td><?php echo $b['from_city']; ?> → <?php echo $b['to_city']; ?></td>
          <td><?php echo date('d M Y',strtotime($b['travel_date'])); ?></td>
          <td><strong><?php echo $b['seat_numbers']; ?></strong></td>
          <td><strong style="color:var(--accent)">₹<?php echo number_format($b['total_amount'],2); ?></strong></td>
          <td><span class="badge <?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <!-- PROFILE -->
  <section id="profile" class="sec">
    <div class="sec-title" style="margin-bottom:18px">My <span>Profile</span></div>
    <div class="pcard">
      <div class="phero">
        <div class="pav"><?php echo strtoupper(substr($user['username'],0,1)); ?></div>
        <div>
          <div class="pname"><?php echo htmlspecialchars($user['username']); ?></div>
          <div class="pemail"><?php echo $user['email']; ?></div>
          <span class="badge paid" style="margin-top:8px">Passenger</span>
        </div>
      </div>
      <div id="profMsg"></div>
      <form onsubmit="saveProfile(event)">
        <div class="pf"><label>Username</label><input type="text"  value="<?php echo htmlspecialchars($user['username']); ?>" readonly></div>
        <div class="pf"><label>Email</label>   <input type="email" value="<?php echo $user['email']; ?>" readonly></div>
        <div class="pf"><label>Password</label><input type="text"  value="<?php echo $user['password']; ?>" readonly></div>
        <div class="pf"><label>Phone</label>   <input type="text"  id="pPhone" value="<?php echo $user['phone']; ?>" placeholder="Phone number"></div>
        <button type="submit" class="savbtn">Save Changes</button>
      </form>
    </div>
  </section>
</main>

<!-- PAYMENT MODAL -->
<div class="ov" id="payOv">
  <div class="pm">
    <div style="font-size:44px;margin-bottom:10px">💳</div>
    <h2>Complete Payment</h2>
    <p>PNR: <strong id="payPnr"></strong></p>
    <div class="pamount" id="payAmt">₹0</div>
    <button class="paybtn" id="rzpBtn">Pay with Razorpay</button>
    <button class="canbtn" onclick="document.getElementById('payOv').classList.remove('show')">Cancel</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
let selSeats=[],curBus=null,pps=0,sts={},bkData=null;
const titles={dashboard:'Dashboard <span>Overview</span>',search:'Search <span>& Book</span>',buses:'View <span>Buses</span>',bookings:'My <span>Bookings</span>',profile:'My <span>Profile</span>'};

function show(id,el){
  document.querySelectorAll('.sec').forEach(s=>s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.ni').forEach(n=>n.classList.remove('active'));
  if(el) el.classList.add('active');
  document.getElementById('pgTitle').innerHTML=titles[id];
}
function toast(msg,ok=true){
  const t=document.getElementById('toast');
  t.textContent=(ok?'✅ ':'❌ ')+msg;
  t.style.borderColor=ok?'var(--accent)':'var(--red)';
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3000);
}
function searchBuses(){
  const from=document.getElementById('from').value.trim();
  const to  =document.getElementById('to').value.trim();
  const date=document.getElementById('date').value;
  const el  =document.getElementById('busResults');
  if(!from||!to||!date){toast('Please fill From, To and Date',false);return;}
  el.innerHTML='<div class="ldg"><div class="spin"></div><p>Searching buses...</p></div>';
  document.getElementById('seatPanel').style.display='none';
  selSeats=[];curBus=null;
  fetch('../search_buses.php?from='+encodeURIComponent(from)+'&to='+encodeURIComponent(to)+'&date='+encodeURIComponent(date))
    .then(function(r){return r.json();})
    .then(function(data){
      if(data.success && data.buses && data.buses.length>0){
        var html='';
        data.buses.forEach(function(b){
          var busJson=encodeURIComponent(JSON.stringify(b));
          html+='<div class="brc" id="bc'+b.id+'" onclick="selectBus(decodeAndParse(\''+busJson+'\'))">';
          html+='<div><div class="bn">'+b.bus_name+'</div>';
          html+='<div class="bm">'+b.from_city+' &rarr; '+b.to_city+' &nbsp;|&nbsp; '+b.travel_date+'</div></div>';
          html+='<div class="ba-right">';
          html+='<div class="ac">'+b.available_seats+'</div><div class="al">seats available</div>';
          html+='<div class="bp">&#8377;'+b.price_per_seat+'/seat</div>';
          html+='<button class="selbtn">Select &rarr;</button>';
          html+='</div></div>';
        });
        el.innerHTML=html;
      } else {
        el.innerHTML='<div class="empty"><i class="fas fa-bus"></i><p>No buses found. Try a different date or route.</p></div>';
      }
    })
    .catch(function(err){
      el.innerHTML='<div class="empty"><i class="fas fa-exclamation-triangle"></i><p>Error: '+err.message+'</p></div>';
    });
}
function decodeAndParse(str){return JSON.parse(decodeURIComponent(str));}
function selectBus(bus){
  document.querySelectorAll('.brc').forEach(function(c){c.classList.remove('sel');});
  var el=document.getElementById('bc'+bus.id);
  if(el) el.classList.add('sel');
  curBus=bus; pps=parseFloat(bus.price_per_seat); selSeats=[];
  document.getElementById('busNameLbl').textContent=bus.bus_name;
  document.getElementById('seatPanel').style.display='block';
  document.getElementById('seatPanel').scrollIntoView({behavior:'smooth'});
  loadSeats(bus.id);
}
function quickBook(id,name,from,to,price,total){
  show('search',document.querySelectorAll('.ni')[1]);
  setTimeout(function(){
    curBus={id:id,bus_name:name,from_city:from,to_city:to,price_per_seat:price,total_seats:total};
    pps=parseFloat(price); selSeats=[];
    document.getElementById('busNameLbl').textContent=name;
    document.getElementById('seatPanel').style.display='block';
    loadSeats(id);
  },300);
}
function loadSeats(busId){
  var g=document.getElementById('seatGrid');
  g.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:20px"><div class="spin"></div></div>';
  fetch('../get_seats.php?bus_id='+busId)
    .then(function(r){return r.json();})
    .then(function(data){
      sts={};
      data.seats.forEach(function(s){sts[s.seat_number]=s.status;});
      renderSeats();
    })
    .catch(function(err){
      g.innerHTML='<div style="color:var(--red);padding:20px">Failed to load seats: '+err.message+'</div>';
    });
}
function renderSeats(){
  const g=document.getElementById('seatGrid');
  g.innerHTML='';
  const total=curBus.total_seats;
  for(let i=1;i<=total;i++){
    const st=sts[i]||'booked';
    const sel=selSeats.includes(i);
    const cls=sel?'selected':st;
    const d=document.createElement('div');
    d.className=`seat ${cls}`;
    d.textContent=i;
    if(st==='available') d.onclick=()=>toggleSeat(i);
    g.appendChild(d);
    // aisle gap after every 2nd column
    if(i%4===2&&i<total){
      const a=document.createElement('div');
      a.className='seat aisle';
      g.appendChild(a);
    }
  }
  updateSum();
}
function toggleSeat(n){
  if(selSeats.includes(n)) selSeats=selSeats.filter(s=>s!==n);
  else selSeats.push(n);
  renderSeats();
}
function updateSum(){
  const total=selSeats.length*pps;
  document.getElementById('selSeats').textContent=selSeats.length?selSeats.sort((a,b)=>a-b).join(', '):'None';
  document.getElementById('priceLabel').textContent='₹'+pps;
  document.getElementById('totalLabel').textContent='₹'+total.toLocaleString();
  document.getElementById('proceedBtn').disabled=selSeats.length===0;
}
function proceedToPayment(){
  if(!selSeats.length) return;
  fetch('../book_tickets.php',{method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({bus_id:curBus.id,seats:selSeats,total_amount:selSeats.length*pps})
  }).then(function(r){return r.json();}).then(function(data){
    if(data.success){
      bkData=data;
      document.getElementById('payPnr').textContent=data.pnr;
      document.getElementById('payAmt').textContent='&#8377;'+(selSeats.length*pps).toLocaleString();
      document.getElementById('payOv').classList.add('show');
      initRzp();
    } else { toast(data.message,false); }
  }).catch(function(err){ toast('Booking error: '+err.message,false); });
}
function initRzp(){
  const opts={
    key:'<?php echo RAZORPAY_KEY_ID; ?>',
    amount:selSeats.length*pps*100,currency:'INR',name:'BusGo',
    description:'Ticket — '+bkData.pnr,order_id:bkData.razorpay_order_id,
    handler:r=>verifyPay(r),
    prefill:{name:'<?php echo $_SESSION["username"]; ?>',email:'<?php echo $_SESSION["user_email"]??""; ?>'},
    theme:{color:'#00d4aa'}
  };
  const rzp=new Razorpay(opts);
  document.getElementById('rzpBtn').onclick=()=>rzp.open();
}
function verifyPay(r){
  fetch('../payment_verify.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({razorpay_order_id:r.razorpay_order_id,razorpay_payment_id:r.razorpay_payment_id,razorpay_signature:r.razorpay_signature,booking_id:bkData.booking_id})})
    .then(r=>r.json()).then(data=>{
      if(data.success) window.location.href='../payment_success.php?pnr='+bkData.pnr;
      else toast('Payment verification failed!',false);
    });
}
function saveProfile(e){
  e.preventDefault();
  const phone=document.getElementById('pPhone').value;
  fetch('../update_profile.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({phone})})
    .then(r=>r.json()).then(data=>{
      document.getElementById('profMsg').innerHTML=data.success
        ?'<p style="color:var(--accent);margin-bottom:14px">✅ Profile updated!</p>'
        :'<p style="color:var(--red);margin-bottom:14px">❌ Update failed.</p>';
    });
}
// Auto-refresh seats every 15s
setInterval(function(){
  if(curBus && document.getElementById('seatPanel').style.display!=='none'){
    fetch('../get_seats.php?bus_id='+curBus.id)
      .then(function(r){return r.json();})
      .then(function(data){
        data.seats.forEach(function(s){sts[s.seat_number]=s.status;});
        selSeats=selSeats.filter(function(n){return sts[n]==='available';});
        renderSeats();
      });
  }
},15000);
</script>
</body>
</html>
