<?php include 'layouts/header.php' ?>
<style>
    .carousel-control-prev,
.carousel-control-next {
    width: 5%; /* Adjust width to make arrows more visible */
    opacity: 1; /* Ensure arrows are fully visible */
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
    background-color: rgba(0, 0, 0, 0.5); /* Dark background for visibility */
    border-radius: 50%; /* Make the arrows circular */
    padding: 10px;
}

</style>
<!-- End Side Vav -->
<a class="close_side_menu" href="javascript:void(0);"></a>


<!-- Start Banner Area -->
<?php if(is_mobile()){ ?>
  <img src="images/mobile.jpg" style="width:100%" alt=""> <br><br>
<?php }else{ ?>
  <img src="images/desktop.png" style="width:100%" alt=""> <br><br>
<?php } ?>
<!-- End Banner Area -->

<!-- Start Promotional Banner Section -->
<div class="container mt-4">
    <?php
    // Get current date
    $today = date("Y-m-d");

    // Fetch promotional banners for today
    $sql_promo = "SELECT * FROM tbl_promo WHERE exp_date >= '$today'";
    $result_promo = $conn->query($sql_promo);

    if ($result_promo->num_rows > 0) {
        $banners = [];
        while ($row_promo = $result_promo->fetch_assoc()) {
            $banners[] = $row_promo['promo_image_name'];
        }

        if (count($banners) == 1) {
            // Display single image
            echo '<div class="text-center">';
            echo '<img src="admin/uploads/' . $banners[0] . '" class="img-fluid rounded shadow" alt="Promotional Banner">';
            echo '</div>';
        } else {
            // Display Bootstrap Carousel for multiple images
            echo '<div id="promoCarousel" class="carousel slide shadow-lg rounded" data-bs-ride="carousel">';
            echo '<div class="carousel-inner">';

            $active = "active";
            foreach ($banners as $banner) {
                echo '<div class="carousel-item ' . $active . '">';
                echo '<img src="admin/uploads/' . $banner . '" class="d-block w-100 rounded" alt="Promo Banner">';
                echo '</div>';
                $active = "";
            }

            echo '</div>';
            echo '<button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">';
            echo '<span class="carousel-control-prev-icon" aria-hidden="true"></span>';
            echo '<span class="visually-hidden">Previous</span>';
            echo '</button>';
            echo '<button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">';
            echo '<span class="carousel-control-next-icon" aria-hidden="true"></span>';
            echo '<span class="visually-hidden">Next</span>';
            echo '</button>';
            echo '</div>';
        }
    }
    ?>
</div>
<!-- End Promotional Banner Section --> <br><br>

<!-- <div class="rbt-categories-area bg-color-white rbt-section-gapBottom">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="swiper category-activation-three rbt-arrow-between icon-bg-gray gutter-swiper-30 ptb--20">
                    <div class="swiper-wrapper">

                        Start Single Category
                        <div class="swiper-slide">
                            <div class="single-slide">
                                <div class="rbt-cat-box rbt-cat-box-1 variation-2 text-center">
                                    <div class="inner">
                                        <div class="thumbnail">
                                            <a href="course-filter-one-toggle.html">
                                                <img src="assets/images/category/image/web-design.jpg" alt="Category Images">
                                            </a>
                                        </div>
                                        <div class="icons">
                                            <img src="assets/images/category/web-design.png" alt="Icons Images">
                                        </div>
                                        <div class="content">
                                            <h5 class="title"><a href="course-filter-one-toggle.html">Web Design</a></h5>
                                            <div class="read-more-btn">
                                                <a class="rbt-btn-link" href="course-filter-one-toggle.html">35 Courses<i class="feather-arrow-right"></i></a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        End Single Category



                    </div>

                    <div class="rbt-swiper-arrow rbt-arrow-left">
                        <div class="custom-overfolow">
                            <i class="rbt-icon feather-arrow-left"></i>
                            <i class="rbt-icon-top feather-arrow-left"></i>
                        </div>
                    </div>

                    <div class="rbt-swiper-arrow rbt-arrow-right">
                        <div class="custom-overfolow">
                            <i class="rbt-icon feather-arrow-right"></i>
                            <i class="rbt-icon-top feather-arrow-right"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->
<div class="whatsapp-layout">
    <a href="https://api.whatsapp.com/send?phone=+97451064278&text=I would like to know about the course?" target="_blank">
        <img src="images/whatsapp.png" class="whatsapp-image" alt="WhatsApp">
    </a>
</div>


<div class="container">
    <div class="row g-5 align-items-center">
        <div class="col-lg-12" data-sal="slide-up" data-sal-duration="700">
            <div class="inner pl--50 pl_sm--5">
                <div class="section-title text-start">
                    <div class="text-center">
                      <span class="subtitle bg-primary-opacity">Top Courses</span>
                    </div>
                    <h2 class="title text-center">Courses Picked For you</h2>
                    <br>
                    <div class="row">
                      <?php
                        $sql_cou="SELECT * FROM tbl_course  LIMIT 0,4";
                        $rs_cou =$conn->query($sql_cou);
                        if($rs_cou->num_rows > 0){
                          while($row_cou = $rs_cou->fetch_assoc()){
                       ?>
                      <div class="col-lg-3">
                        <div class="course-grid-3">
                            <div class="rbt-card variation-01 rbt-hover">
                                <div class="rbt-card-img">
                                    <a href="course-details.php?id=<?= $row_cou['c_id'] ?>">
                                        <img src="admin/images/<?= $row_cou['c_thumb_image'] ?>" alt="Card image">
                                    </a>
                                </div>
                                <div class="rbt-card-body">

                                    <h4 class="rbt-card-title" style="font-size:18px;"><a href="course-details.php?id=<?= $row_cou['c_id'] ?>"><?= $row_cou['c_name'] ?></a>
                                    </h4>

                                    <ul class="rbt-meta">
                                        <li><i class="feather-book"></i>12 Lessons</li>
                                    </ul>

                                    <div class="rbt-card-bottom">
                                        <a class="rbt-btn-link" href="course-details.php?id=<?= $row_cou['c_id'] ?>">Learn
                                            More<i class="feather-arrow-right"></i></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                      </div>
                    <?php } } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br><br>
</div>





<div class="rbt-about-area about-style-1 bg-color-white rbt-section-gap">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="content">
                    <img src="images/about_us.jpg?version=new" alt="About Images">
                </div>
            </div>
            <div class="col-lg-6" data-sal="slide-up" data-sal-duration="700">
                <div class="inner pl--50 pl_sm--5">
                    <div class="section-title text-start">
                        <span class="subtitle bg-primary-opacity">About Sherwood Campus</span>
                        <h2 class="title">What is Sherwood Campus For You?.</h2>
                        <p class="description mt--20"><strong>Sherwood Campus</strong>
                           is a place where students can shape their aspirations, develop their talents,
                           and pursue their dreams with a tailored and supportive educational approach and we are
                            dedicated to helping working professionals take their careers to the next level. <br>
                            We understand that the journey of a working individual is distinct, filled with responsibilities and aspirations.
                           </p>
                        <div class="read-more-btn mt--40">
                            <a class="rbt-btn btn-sherwood" href="about-us.php">
                                <span data-text="More About Us">
                            More About Us
                        </span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row g-5 align-items-center">
        <div class="col-lg-12" data-sal="slide-up" data-sal-duration="700">
            <div class="inner pl--50 pl_sm--5">
                <div class="section-title text-start">
                    <h2 class="title text-center">OUR STUDY DESTINATIONS</h2>
                    <br>
                    <div class="row">
                      <?php
                        $sql = "SELECT * FROM tbl_flags LIMIT 0,4";
                        $rs = $conn->query($sql);
                        if($rs->num_rows > 0){
                          while($rowFlag = $rs->fetch_assoc()){
                       ?>
                       <div class="col-lg-3 col-6">
                             <div class="rbt-card variation-01 rbt-hover">
                                 <div class="rbt-card-img" onclick="listOfUni(<?= $rowFlag['flag_id'] ?>)">
                                         <img src="admin/flag_images/<?= $rowFlag['flag_image'] ?>" alt="Card image" style="cursor:pointer;width:100%;height:120px;">
                                 </div>
                                 <div class="rbt-card-body">
                                     <h4 class="rbt-card-title" style="font-size:18px;"><?= $rowFlag['flag_name'] ?></h4>
                                     <div class="rbt-card-bottom">
                                         <a class="rbt-btn-link" style="cursor:pointer;" onclick="listOfUni(<?= $rowFlag['flag_id'] ?>)">View Universities<i class="feather-arrow-right"></i></a>
                                     </div>
                                 </div>
                             </div>
                       </div>
                     <?php } } ?>


                     </div>
                </div>
            </div>
        </div>
    </div>

    <div class="read-more-btn mt--40 text-center">
        <a class="rbt-btn btn-sherwood btn-sm" href="https://api.whatsapp.com/send?phone=+97451064278&text=I%20would%20like%20to%20know%20about%20the%20course?">
            <span data-text="More About Us">
      FREE CONSULTATION
    </span>
        </a>
    </div> <br> <br>

</div>
<div class="modal fade" id="listOfUni" tabindex="-1" role="dialog" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header modal-header-primary">
            <h3>List of Universities </h3>
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true" onclick="closeModal()">脳</button>
         </div>
      <div class="modal-body" id="show_uni">

      </div>
</div>
<!-- /.modal-content -->
</div>
<!-- /.modal-dialog -->
</div>

<!-- Enhanced Bootstrap Carousel Section with Professional Card Design -->
<section class="container mt-5">
    <div class="text-center mb-5">
        <h2 class="title">Testimonials</h2>
        <p class="text-muted">Discover what our students say about their journey with us</p>
    </div>

    <div id="nameCarousel" class="carousel slide shadow-lg rounded" data-bs-ride="carousel">
        <div class="carousel-inner bg-white rounded">
            <?php
                $sql_rev = "SELECT * FROM tbl_reviews";
                $rs_rev = $conn->query($sql_rev);
                $count = 0;
                $active = true; // To mark the first item as active

                if($rs_rev->num_rows > 0){
                    while($row_rev = $rs_rev->fetch_assoc()){
                        if ($count % 3 == 0) { // Open a new carousel item for every 3 reviews
                            if ($count > 0) {
                                echo '</div></div>'; // Close previous item after 3 reviews
                            }
                            echo '<div class="carousel-item '. ($active ? 'active' : '') .'">';
                            echo '<div class="row g-3">';
                            $active = false; // Disable active after the first iteration
                        }
            ?>
                <div class="col-md-4">
                    <div class="card p-4 border-0 bg-white shadow-sm rounded-3">
                        <div class="card-body">
                            <h5 class="card-title mb-1 text-dark fw-bold"><?= $row_rev['rev_name'] ?></h5>
                            <p class="card-text text-muted"><?= $row_rev['rev_content'] ?></p>
                        </div>
                    </div>
                </div>
            <?php
                    $count++;
                    }
                    echo '</div></div>'; // Close the last item
                }
            ?>
        </div>

        <!-- Carousel Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#nameCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#nameCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>






<!-- Start CallTo Action Area  -->
<!-- <div class="rbt-call-to-action-area rbt-section-gap bg-gradient-8">
    <div class="rbt-callto-action rbt-cta-default style-6">
        <div class="container">
            <div class="row g-5 align-items-center content-wrapper">
                <div class="col-xxl-3 col-xl-3 col-lg-6">
                    <div class="inner">
                        <div class="content text-start">
                            <h2 class="title color-white mb--0">Scholarship Programs</h2>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-xl-6 col-lg-6">
                    <div class="inner-content text-start">
                        <p class="color-white">Apply for Admission in Post Graduate Diploma. Application Deadline: 26th September 2022 路 Undergraduate & Graduate Admission: Fall 2022 Semester 路 20% Waiver on ...
                        </p>
                    </div>
                </div>
                <div class="col-xxl-3 col-xl-3 col-lg-6">
                    <div class="call-to-btn text-start text-xl-end">
                        <a class="rbt-btn btn-white hover-icon-reverse" href="#">
                            <span class="icon-reverse-wrapper">
                        <span class="btn-text">Histudy Financial Aid</span>
                            <span class="btn-icon"><i class="feather-arrow-right"></i></span>
                            <span class="btn-icon"><i class="feather-arrow-right"></i></span>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> -->
<!-- End CallTo Action Area  -->


<!-- Start Contact Me Area  -->
<div class="rbt-contact-me bg-color-white rbt-section-gap">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="content">
                    <div class="section-title text-start">
                        <h2 class="title">Want to stay informed about new courses & Sherwood Campus?</h2>
                        <p class="description mt--20">
                          Don't miss out on the latest happenings at Sherwood Campus! Stay connected with us to receive timely updates about new courses, exciting events, and important announcements.
                        </p>
                        <div class="social-share-wrapper mt--30">
                            <h5>You can also follow us on:</h5>
                            <?php include 'layouts/sc_link.php'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 offset-xl-1">
                <div class="rbt-contact-form contact-form-style-1 max-width-auto">

                    <form id="contact-form" class="w-100">
                        <div class="form-group">
                            <input name="con_name" type="text" />
                            <label>Name</label>
                            <span class="focus-border"></span>
                        </div>
                        <div class="form-group">
                            <input name="con_email" type="email">
                            <label>Email</label>
                            <span class="focus-border"></span>
                        </div>
                        <div class="form-group">
                            <input type="text">
                            <label>Phone</label>
                            <span class="focus-border"></span>
                        </div>
                        <div class="form-group">
                            <textarea></textarea>
                            <label>Message</label>
                            <span class="focus-border"></span>
                        </div>
                        <div class="form-submit-group">
                            <button type="submit" class="rbt-btn radius-round btn-sherwood hover-icon-reverse">
                                <span class="icon-reverse-wrapper">
                                    <span class="btn-text">Send Message</span>
                                <span class="btn-icon"><i class="feather-arrow-right"></i></span>
                                <span class="btn-icon"><i class="feather-arrow-right"></i></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Contact Me Area  -->




<?php include 'layouts/footer.php'; ?>
<script type="text/javascript">
  function playIntroVideo(){
    $('#introVideo').modal('show');
  }

  console.log('The DNS for Qatar has not propagated to the QR license. Please reach out to the Qatar DNS Center to address and resolve this matter.');
  console.log('You are required to include a "Terms and Conditions" page on your website. Currently, we are unable to locate the "Terms and Conditions" page on your website');
</script>
<script type="text/javascript">
  function listOfUni(fid){
    $('#listOfUni').modal('show');
    $('#show_uni').load('ajax_pages/show_uni.php',{ id:fid });
  }
  function closeModal(){
    $('#listOfUni').modal('hide');
  }
</script>
</body>

</html>
