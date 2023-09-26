
  <?php
      require('./env.php');
      require('./connection.php');
  ?>

  <!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8" />
      <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link rel="stylesheet" href="style.css" />
      <link
        rel="icon"
        type="image/x-icon"
        href="WhatsApp Image 2023-09-08 at 15.54.36.jpg"
      />
      <link rel="preconnect" href="https://fonts.googleapis.com" />
      <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
      <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300&display=swap"
        rel="stylesheet"
      />
      <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
        integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"
      />

      <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css"
      />

      <title>GladWheel</title>
    </head>
    <body>
      <nav>
        <div class="container" >
          <div class="logo">
            <img src="LOGO 2_page-0001.jpg" alt="LOGO" />
          </div>
          <ul class="nav-links">
            <i class="fa-solid fa-xmark nav-menu-close-btn" ></i>
            <!--  ancher is for navigating different section of the page for later use-->
            <li><a href="#home">Home</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#booking">Booking</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
          <div class="nav-menu-btn">
            <i class="fa-solid fa-bars"></i>
          </div>
        </div>
      </nav>

      <?php
        if($_SERVER['REQUEST_METHOD']==='POST'){

          $name = (isset($_POST['name']) && is_string($_POST['name']) && strlen(trim($_POST['name'])) > 0 && strlen(trim($_POST['name'])) < 100) ? $_POST['name'] : "";
          $email = (isset($_POST['email']) && is_string($_POST['email']) && strlen(trim($_POST['email'])) > 0 && strlen(trim($_POST['email'])) < 100) ? $_POST['email'] : "";
          $phone = (isset($_POST['phone']) && is_string($_POST['phone']) && strlen(trim($_POST['phone'])) > 0 && strlen(trim($_POST['phone'])) < 100) ? $_POST['phone'] : "";
          $state = (isset($_POST['state']) && is_string($_POST['state']) && strlen(trim($_POST['state'])) > 0 && strlen(trim($_POST['state'])) < 100) ? $_POST['state'] : "";
          $city = (isset($_POST['city']) && is_string($_POST['city']) && strlen(trim($_POST['city'])) > 0 && strlen(trim($_POST['city'])) < 100) ? $_POST['city'] : "";
          $pincode = (isset($_POST['pincode']) && is_string($_POST['pincode']) && strlen(trim($_POST['pincode'])) > 0 && strlen(trim($_POST['pincode'])) < 100) ? $_POST['pincode'] : "";
          $address = (isset($_POST['address']) && is_string($_POST['address']) && strlen(trim($_POST['address'])) > 0 && strlen(trim($_POST['address'])) < 100) ? $_POST['address'] : "";
          if($name && $email && $phone && $state && $city && $pincode && $address){

            $merchantTransactionId='';
            $merchantUserId='';
            
              //generate the merchantTransactionId
            function generateRandomWord($length ) {
                $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
                
                $randomWord = '';
                
                for ($i = 0; $i < $length; $i++) {
                    $randomWord .= $characters[rand(0, strlen($characters) - 1)];
                }
                return $randomWord;
            }

              $transactionId='';

            $merchantTransactionId=generateRandomWord(20);
            $merchantUserId=generateRandomWord(10);

              //store the user data 
              $sql="insert into user (name,email,phone,state,city,address,pincode,transactionId,merchantTransactionId,merchantUserId) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?,?)";
              $statement=$mysqli->prepare($sql);

              $statement->bind_param("ssssssssss",$name,$email,$phone,$state,$city,$address,$pincode,$transactionId,$merchantTransactionId,$merchantUserId);


              
              $result=$statement->execute();

              $curl = curl_init();

              $data= json_encode([
                "merchantId"=> $GLOBALS['merchantId'],
                "merchantTransactionId"=>$GLOBALS['merchantTransactionId'],
                "merchantUserId"=>$merchantUserId,
                "amount"=>10000*100,
                "redirectUrl"=>$GLOBALS['baseUrl'].'/paymentprocess.php',
                "redirectMode"=> "POST",
                "callbackUrl"=>"",
                "mobileNumber"=> $GLOBALS['mobileNumber'],
                "paymentInstrument"=>[
                  "type"=> "PAY_PAGE",
                ]
              ]);

       

              $payload=base64_encode($data);
              
              $checksum=hash('sha256',$payload."/pg/v1/pay".$GLOBALS['saltKey'])."###".$GLOBALS['saltIndex'];
              
              curl_setopt_array($curl, [
                  CURLOPT_URL => "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS =>json_encode([
                    "request"=>$payload,
                  ]),
                  CURLOPT_HTTPHEADER => [
                    "Content-Type: application/json",
                    "accept: application/json",
                    "X-VERIFY:".$checksum,
                  ],
              ]);
              
              $response = curl_exec($curl);
              $err = curl_error($curl);
              
              curl_close($curl);
              
              if ($err) {
                
                echo "<div class='formError'> ".$err." <i class='fa-solid fa-xmark formError-close-btn' ></i> </div>";
              } 
              else {
                $response=json_decode($response);
                if($response->success){
                  $responseData=$response->data;
                  $responseInstrument=$responseData->instrumentResponse;
                  $redirectInfo=$responseInstrument->redirectInfo;
                  $url=$redirectInfo->url;
                  
                  header("Location:".$url);
                }
                else{
                  echo "<div class='formError'> ".$response->message." <i class='fa-solid fa-xmark formError-close-btn' ></i> </div>";
                }
              }

          }
          else{
            echo "<div class='formError'>Invalid input <i class='fa-solid fa-xmark formError-close-btn' ></i> </div>";
          }
        }
      ?>

      <div class="main" id="home">

        <div class="product-section">
          <div class="title">
            <div class="words">
              <span>welcome to wheelease solution</span>
              <span>where mobility knows no bond.</span>

            </div>
          </div>
          <div class="main-product-img">
            <img src="mainimg.jpg" alt="" />
          </div>
        </div>

        <div class="product-feature">
          <div class="heading">
            <h1>
              Now get universal clip on kit which fits on all wheelchair
            </h1>
          </div>
          <div class="description">
            <p>
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolores, asperiores!
            </p>
          </div>
          <div class="product-image">
            <img src="FF_2.5_m_blk_sl_side_768x768_clipdrop-enhance.png" alt="" />
          </div>
        </div>
        
        <div class="product-feature">
          <div class="heading">
            <h1>
              With best selling pricing in the entire market
            </h1>
          </div>
          <div class="description">
            <p>
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolores, asperiores!
            </p>
          </div>
          <div class="product-image">
            <img src="rio_firefly_2.51_side_gry_1_768x768.png" alt="" />
          </div>
        </div>

        <div class="product-feature">
          <div class="heading">
            <h1>
              With best selling pricing in the entire market
            </h1>
          </div>
          <div class="description">
            <p>
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolores, asperiores!
            </p>
          </div>
          <div class="product-image">
            <img src="rio_firefly_2.51_side_gry_1_768x768.png" alt="" />
          </div>
        </div>

        <div class="product-feature">
          <div class="heading">
            <h1>
              With best selling pricing in the entire market
            </h1>
          </div>
          <div class="description">
            <p>
              Lorem, ipsum dolor sit amet consectetur adipisicing elit. Dolores, asperiores!
            </p>
          </div>
          <div class="product-image">
            <img src="rio_firefly_2.51_side_gry_1_768x768.png" alt="" />
          </div>
        </div>


    

        <div class="payment-section" id="booking">
           <div class="price-section">
              <h1>Price</h1>
              <h1>INR 50,0000</h1>
              <p>Pay INR to preorder your product</p>
              <p>The product will be dispatched within 2 months</p>
          </div>
          <div class="user-form">
            <form action="#" method="post">
                <div class="input-wrapper">
                  <label for="name">Full Name</label>
                  <input type="text" name="name" id="name" >
                </div>
                <div class="input-wrapper">
                  <label for="email">Email</label>
                  <input type="email" name="email" id="email" >
                </div>
                <div class="input-wrapper">
                  <label for="phone">Phone</label>
                  <input type="text" name="phone" id="email" >
                </div>
                        
                <div class="input-wrapper">
                  <label for="address">Address</label>
                  <input type="text" name="address" id="address">
                </div>

                <div class="input-wrapper">
                  <label for="city">City</label>
                  <input type="text" name="city" id="city">
                </div>

                <div class="input-wrapper">
                  <label for="state">State</label>
                  <input type="text" name="state" id="state">
                </div>

                <div class="input-wrapper">
                  <label for="pincode">PinCode</label>
                  <input type="text" name="pincode" id="pincode">
                </div>
                <div class="input-wrapper">
                  <button type="submit">PreOrder @10,000</button>
                </div>
            </form> 
          </div>
        </div>

        <!-- team section -->
        <div class="team-head">
        <h1>OUR TEAM</h1>
      </div>
      <div class="team">
        <div class="profile-card">
          <img src="taha.jpg" alt="">
          <h2>FAISAL ASHRAF NOMANI</h2>
          <p>Role: CEO</p>
          <div class="profile-details">
            <p>I'm Faisal, a passionate advocate for inclusivity and social change. I wear many hats – from being a 'bike tinkerer' to a dedicated field researcher, employment counselor, disability rights activist, sit-down comedian, and speaker. I take immense pride in having facilitated employment opportunities for approximately 500 People with Disabilities (PwDs) across various sectors, thus positively impacting their lives. My dedication hasn't gone unnoticed. I was honored to receive the prestigious Helen Keller Award in 2022 from NCPEDP for my unwavering support in increasing employment opportunities for PwDs. My mission is clear: creating inclusive workplaces and empowering individuals with diverse abilities to not just find jobs, but to thrive in their careers. Join me on this journey towards a more inclusive and equitable world. Together, we can make a meaningful difference</p>  
          </div>
          <button class="show-more">Show More</button>
       </div>
        <div class="profile-card">
          <img src="maqool.jpg" alt="">
          <h2>MAQOOL SAIFI</h2>
          <p>Role: CEO</p>
          <div class="profile-details">
            <p>I'm Maqool Saifi, a software engineer with a penchant for designing and fabricating life-enhancing products. In 2023, I embarked on a journey as a YouTuber, sharing my passion for innovation. But my commitment goes beyond that; since 2022, I've been actively involved in the disability sector. Recognizing the unique challenges faced by individuals with disabilities, I joined forces with a diverse team, including wheelchair users, to launch Wheelease Solutions, a startup dedicated to enhancing the mobility of wheelchair users. We've recently pitched our innovative product at DIC Meet 2023, driven by our mission to improve the lives of those with disabilities through thoughtful and impactful solutions.</p>  
          </div>
          <button class="show-more">Show More</button>
       </div>
        <div class="profile-card">
          <img src="taha.jpg" alt="">
          <h2>TAHA NAFEES</h2>
          <p>Role: CEO</p>
          <div class="profile-details">
            <p>I'm Taha Nafees, a dedicated software engineer with a passion for inclusivity. Alongside my tech career, I run a YouTube channel called "Dare with Gear." Since 2018, I've been deeply involved in the disability sector. I've had the privilege of working with a diverse team, including wheelchair users and IITians. Together, we designed a groundbreaking reverse trike kit to enhance mobility for people with disabilities, surpassing side-wheel attachments for bikes. We proudly secured the 4th rank in India at the Red Brick Summit at IIM Ahmedabad. We also presented our kit at the prestigious TiE Global Summit at Taj Palace, Delhi, and at the DIC Meet 2023. In addition, we've developed electric carts as a dual-purpose solution, addressing mobility challenges and creating employment opportunities for individuals with disabilities.I am committed to making a lasting impact in the field of accessibility and mobility</p>  
          </div>
          <button class="show-more">Show More</button>
       </div>
        <div class="profile-card">
          <img src="maqool.jpg" alt="">
          <h2>FAISAL ASHRAF NOMANI</h2>
          <p>Role: CEO</p>
          <div class="profile-details">
            <p>I'm Faisal, a passionate advocate for inclusivity and social change. I wear many hats – from being a 'bike tinkerer' to a dedicated field researcher, employment counselor, disability rights activist, sit-down comedian, and speaker. I take immense pride in having facilitated employment opportunities for approximately 500 People with Disabilities (PwDs) across various sectors, thus positively impacting their lives. My dedication hasn't gone unnoticed. I was honored to receive the prestigious Helen Keller Award in 2022 from NCPEDP for my unwavering support in increasing employment opportunities for PwDs. My mission is clear: creating inclusive workplaces and empowering individuals with diverse abilities to not just find jobs, but to thrive in their careers. Join me on this journey towards a more inclusive and equitable world. Together, we can make a meaningful difference</p>  
          </div>
          <button class="show-more">Show More</button>
       </div>
        <div class="profile-card">
          <img src="taha.jpg" alt="">
          <h2>AYAN BAIG</h2>
          <p>Role: CEO</p>
          <div class="profile-details">
            <p>I'm Ayan Baig, a final-year computer engineering student, and a disability sector enthusiast since the age of 14. My focus lies in enhancing the mobility of wheelchair users in India. Collaborating with a diverse team of IT professionals and wheelchair users, we developed a transformative reverse trike kit to address mobility challenges faced by disabled bike riders. Our innovation garnered recognition at esteemed platforms such as the Red Brick Summit and TiE Global Summit, earning us a notable 4th place at the former. Additionally, I clinched the 1st position at the DIICE AKAM contest, a sustainable business ideathon, and the Dr. B.R Ambedkar university. Currently, I co-manage a startup dedicated to improving wheelchair riding through a clip-on kit, while also running a YouTube channel, "Dare with Gear." My journey is defined by a steadfast commitment to empower individuals with disabilities and pioneer innovative solutions for enhanced mobility and inclusivity.</p>  
          </div>
          <button class="show-more">Show More</button>
       </div>
        

      </div>
    </div>

        <div class="customer-review swiper">
          <div class="content swiper-wrapper">
            <div class="swiper-slide card">
              <div class="card-content">
                <img src="taha.jpg" alt="" />
                <div class="description">
                  Highly Recommended.
                </div>
                <div class="name">~Taha</div>
                <div class="rating">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                </div>
              </div>
            </div>
            <div class="swiper-slide card">
              <div class="card-content">
                <img src="rio_firefly_2.5_side_gry_1_768x768.png" alt="" />
                <div class="description">
                  Great choice at such a reasonable price.
                </div>
                <div class="name">~Yusuf</div>
                <div class="rating">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                </div>
              </div>
            </div>
            <div class="swiper-slide card">
              <div class="card-content">
                <img src="maqool.jpg" alt="" />
                <div class="description">
                  Smooth and effortless system.
                </div>
                <div class="name">~Maqool</div>
                <div class="rating">
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                  <i class="fas fa-star"></i>
                </div>
              </div>
            </div>
          </div>

          <div class="swiper-button-prev"></div>
          <div class="swiper-button-next"></div>

          <div class="swiper-scrollbar"></div>
        </div>




        <div class="faq-section" id="faq">
          <h2>FAQ</h2>
          <div class="ques">
            <li>
              <span>Can I drive clip ease without driving lincence?.</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>Yes you can drive clip ease without lincence because its speed is 25kmph.</li>
          </div>
          <div class="ques">
            <li>
              <span>Can I drive clip ease at night?.</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>Yes you can drive clip ease at night because it has dual headlight setup for better visibility at night.</li>
          </div>
          <div class="ques">
            <li>
              <span>Can I go reverse in clip ease?</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>Yes you have a dedicated button to shift the driving mode. </li>
          </div>
          <div class="ques">
            <li>
              <span>How much distance can I cover in one charge?</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>You can go 25km in single charge.</li>
          </div>
          <div class="ques">
            <li>
              <span>Do I get any warranty of clip ease</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>Yes you have one year warranty.</li>
          </div>
          <div class="ques">
            <li>
              <span>Can I pay in installments?</span>
              <i class="fa-solid fa-angle-down"></i>
            </li>
            <li>Yes you can pay in installments.</li>
          </div>
        </div>




      </div>

  

      <footer id="contact">
        <div class="footer-upward-section">
          <div class="logo">
            <img src="WhatsApp Image 2023-09-08 at 15.54.36.jpg" alt="">
          </div>  
          <div class="follow-btn">
            <p>Follow us</p>
            <div class="icons">
              <a href="">
                <i class="fa-brands fa-facebook"></i>
              </a>
              <a href="">
                <i class="fa-brands fa-twitter"></i>
              </a>
            </div>
          </div>
          <div class="contact-us">
            <p>contact us</p>
            <p class="phone">+91 99298349</p>
            <p class="email">gladwheel@gmail.com</p>
          </div>
        </div>
      
        <div class="footer-downward-section">
          <div class="copyright">
            <p>All copy rights reserved. Glad Wheel</p>
          </div>
          <div class="terms-condition">
            <p>Terms and Conditions</p>
          </div>
        </div>
    </footer>



    
      <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
      <script>
        const swiper = new Swiper(".swiper", {
          direction: "horizontal",
          loop: true,

          pagination: {
            el: ".swiper-pagination",
          },

          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },

          scrollbar: {
            el: ".swiper-scrollbar",
          },
        });

        //text animation 
        const words=document.querySelector('.product-section .words').children;
        let index=0;
        function animateText(){
          for(let i=0;i<words.length;i++){
            words[i].classList.remove('text-visible');
          }
          words[index].classList.add('text-visible');
          if(index>=words.length-1){
            index=0;
          }
          else{
            index++;
          }
          setTimeout(animateText,6000);
        }

        window.addEventListener('load',()=>{
          animateText()
        })

        //faq section animation
        const faqs=document.querySelectorAll('.faq-section .ques');
        faqs.forEach((faq)=>{
          faq.addEventListener('click',()=>{
            console.log(faq.children[1]);
            faq.children[0].classList.toggle('expand')
            faq.children[1].classList.toggle('show');
          })
        })

        //nav-menu-btn handler
        
        const menuBtn=document.querySelector('.nav-menu-btn');
        if(menuBtn){
          menuBtn.addEventListener('click',()=>{
            const element=menuBtn.previousElementSibling;
            const style=window.getComputedStyle(element);
            
            if(style.display==='none'){
              element.style.display='flex';
            }
            else if(style.display==='block' || style.display==='inline-block' || style.display==='inline' || style.display==='flex'){
              element.style.display='none';
            }
          })
        }

        const menuCloseBtn=document.querySelector('.nav-menu-close-btn');

        menuCloseBtn.addEventListener('click',()=>{
          const element=menuCloseBtn.parentElement;
          const style=window.getComputedStyle(element);
            if(style.display==='block' || style.display==='inline-block' || style.display==='inline' || style.display==='flex' || style.display==='grid'){
              element.style.display='none';
            }
        })


        //form error close btn

        const formCloseBtn=document.querySelector('.formError-close-btn');
        if(formCloseBtn){
          formCloseBtn.addEventListener('click',()=>{
            formCloseBtn.parentElement.style.display="none";
          })
        }

        //team section js
        document.addEventListener('DOMContentLoaded', function() {
            const showMoreButtons = document.querySelectorAll('.show-more');
  
            showMoreButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const profileDetails = this.parentElement.querySelector('.profile-details');
                    profileDetails.style.display = profileDetails.style.display === 'none' ? 'block' : 'none';
                    this.textContent = profileDetails.style.display === 'none' ? 'Show More' : 'Show Less';
                });
            });
        });

      </script>
    </body>
  </html>
