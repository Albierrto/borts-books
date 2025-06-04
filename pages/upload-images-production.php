<?php
require_once '../includes/security.php';
require_once '../includes/admin-auth.php';
require_once '../includes/db.php';

// Start secure session
secure_session_start();

// Set security headers
set_security_headers();

// Require admin authentication
if (!admin_is_logged_in()) {
    header('Location: ../admin/admin-login.php');
    exit;
}

// Check rate limiting
if (!check_rate_limit('upload_images_production', 10, 300)) {
    http_response_code(429);
    die('Too many upload page requests. Please wait before trying again.');
}

// Validate and sanitize product ID
$product_id = isset($_GET['product_id']) ? validate_int($_GET['product_id']) : null;
if (!$product_id || $product_id <= 0) {
    header('Location: admin-dashboard.php');
    exit;
}

// Log access for security monitoring
log_security_event('upload_images_production_access', ['product_id' => $product_id], 'low');

// Get product details with error handling
try {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        log_security_event('invalid_product_access', ['product_id' => $product_id], 'medium');
        echo "Product not found!";
        exit;
    }
} catch (Exception $e) {
    log_security_event('database_error', ['error' => $e->getMessage()], 'high');
    echo "Database error occurred.";
    exit;
}

// Get existing images with schema compatibility
try {
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback for older schema
    try {
        $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$product_id]);
        $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e2) {
        log_security_event('image_query_failed', ['error' => $e2->getMessage()], 'medium');
        $existing_images = [];
    }
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cdefs%3E%3ClinearGradient id='grad' x1='0%25' y1='0%25' x2='100%25' y2='100%25'%3E%3Cstop offset='0%25' style='stop-color:%23667eea;stop-opacity:1' /%3E%3Cstop offset='100%25' style='stop-color:%23764ba2;stop-opacity:1' /%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width='100' height='100' rx='15' fill='url(%23grad)'/%3E%3Cpath d='M25 20h50c2.5 0 4.5 2 4.5 4.5v51c0 2.5-2 4.5-4.5 4.5H25c-2.5 0-4.5-2-4.5-4.5v-51c0-2.5 2-4.5 4.5-4.5z' fill='white'/%3E%3Cpath d='M30 30h40v5H30z' fill='%23667eea'/%3E%3Cpath d='M30 40h35v3H30z' fill='%23999'/%3E%3Cpath d='M30 47h30v3H30z' fill='%23999'/%3E%3Cpath d='M30 54h25v3H30z' fill='%23999'/%3E%3C/svg%3E">
    <title>Upload Images - <?php echo htmlspecialchars($product['title']); ?></title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5}
        .container{max-width:1000px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1)}
        .header{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;padding:20px;border-radius:8px;margin-bottom:20px;text-align:center}
        .section{background:#f8f9fa;padding:20px;border-radius:8px;margin-bottom:20px;border:1px solid #e9ecef}
        .drop-zone{border:2px dashed #ccc;padding:40px;text-align:center;border-radius:8px;cursor:pointer;transition:all 0.3s}
        .drop-zone:hover{border-color:#007cba;background:#f0f8ff}
        .btn{background:#007cba;color:white;padding:10px 20px;border:none;border-radius:4px;cursor:pointer;text-decoration:none;display:inline-block}
        .btn:hover{background:#005a87}
        .btn-sm{padding:5px 10px;font-size:12px;margin:2px}
        .btn-danger{background:#dc3545}
        .btn-success{background:#28a745}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:15px;margin-top:20px}
        .item{border:1px solid #ddd;border-radius:8px;padding:10px;text-align:center;background:white}
        .item img{max-width:100%;height:150px;object-fit:cover;border-radius:4px}
        .badge{background:#28a745;color:white;padding:2px 8px;border-radius:4px;font-size:12px}
        .alert{padding:15px;margin:10px 0;border-radius:4px}
        .alert-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
        .alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
        .alert-info{background:#d1ecf1;color:#0c5460;border:1px solid #bee5eb}
        .preview{margin:10px 0;padding:8px;border:1px solid #ddd;border-radius:4px;display:inline-block}
        @media(max-width:768px){.grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}}
    </style>
</head>
<body>
    <div class="container">
        <a href="edit-product.php?id=<?php echo $product_id; ?>" class="btn" style="margin-bottom:20px">← Back to Edit Product</a>
        
        <div class="header">
            <h1>📸 Upload Images - Production</h1>
            <p>Product: <?php echo htmlspecialchars($product['title']); ?></p>
            <p>Current Images: <?php echo count($existing_images); ?></p>
        </div>
        
        <div class="alert alert-info">
            <strong>Security Notice:</strong> This is a secure admin-only upload interface with CSRF protection, rate limiting, and file validation.
        </div>
        
        <div id="message"></div>
        
        <!-- Upload Section -->
        <div class="section">
            <h3>Upload New Images</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                
                <div class="drop-zone" onclick="document.getElementById('fileInput').click()">
                    <p><strong>📁 Click to Select Images</strong></p>
                    <p>JPG, PNG, GIF, WEBP (max 10MB each)</p>
                </div>
                
                <input type="file" id="fileInput" name="images[]" multiple accept="image/*" style="display:none">
                
                <div id="preview"></div>
                
                <button type="button" id="uploadBtn" class="btn" style="margin-top:15px;display:none">Upload Images</button>
            </form>
        </div>
        
        <!-- Existing Images -->
        <?php if (!empty($existing_images)): ?>
        <div class="section">
            <h3>Existing Images (<?php echo count($existing_images); ?>)</h3>
            <div class="grid">
                <?php foreach ($existing_images as $image): ?>
                <div class="item" id="img-<?php echo $image['id']; ?>">
                    <?php 
                    // Secure filename handling
                    $filename = !empty($image['filename']) ? htmlspecialchars($image['filename']) : htmlspecialchars($image['image_url']);
                    ?>
                    <img src="../assets/img/products/<?php echo $filename; ?>" alt="Product image">
                    <div style="margin-top:8px">
                        <?php if (isset($image['is_main']) && $image['is_main']): ?>
                            <span class="badge">Main Image</span>
                        <?php else: ?>
                            <button class="btn btn-success btn-sm" onclick="setMain(<?php echo $image['id']; ?>)">Set Main</button>
                        <?php endif; ?>
                        <button class="btn btn-danger btn-sm" onclick="deleteImg(<?php echo $image['id']; ?>)">Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        const fileInput=document.getElementById('fileInput');
        const preview=document.getElementById('preview');
        const uploadBtn=document.getElementById('uploadBtn');
        const messageDiv=document.getElementById('message');
        let files=[];
        
        fileInput.addEventListener('change',function(e){
            files=Array.from(e.target.files);
            showPreview();
        });
        
        function showPreview(){
            preview.innerHTML='';
            if(files.length>0){
                uploadBtn.style.display='inline-block';
                files.forEach((file,i)=>{
                    const div=document.createElement('div');
                    div.className='preview';
                    div.innerHTML=`${file.name} (${(file.size/1024/1024).toFixed(2)}MB) <button onclick="removeFile(${i})" style="background:#dc3545;color:white;border:none;padding:2px 6px;border-radius:2px;margin-left:8px">×</button>`;
                    preview.appendChild(div);
                });
            }else{
                uploadBtn.style.display='none';
            }
        }
        
        function removeFile(i){
            files.splice(i,1);
            showPreview();
        }
        
        uploadBtn.addEventListener('click',function(){
            if(files.length===0)return;
            
            const formData=new FormData();
            formData.append('csrf_token','<?php echo $csrf_token; ?>');
            formData.append('product_id',<?php echo $product_id; ?>);
            files.forEach(file=>formData.append('images[]',file));
            
            uploadBtn.disabled=true;
            uploadBtn.textContent='Uploading...';
            
            fetch('process-upload-images.php',{
                method:'POST',
                body:formData
            })
            .then(response=>response.json())
            .then(data=>{
                if(data.success){
                    showMessage(data.message,'success');
                    setTimeout(()=>location.reload(),2000);
                }else{
                    showMessage(data.message,'error');
                }
            })
            .catch(()=>showMessage('Upload failed. Please try again.','error'))
            .finally(()=>{
                uploadBtn.disabled=false;
                uploadBtn.textContent='Upload Images';
            });
        });
        
        function showMessage(text,type){
            messageDiv.innerHTML=`<div class="alert alert-${type}">${text}</div>`;
            setTimeout(()=>messageDiv.innerHTML='',5000);
        }
        
        function deleteImg(id){
            if(!confirm('Delete this image?'))return;
            
            const formData=new FormData();
            formData.append('csrf_token','<?php echo $csrf_token; ?>');
            formData.append('image_id',id);
            
            fetch('delete-image.php',{
                method:'POST',
                body:formData
            })
            .then(response=>response.json())
            .then(data=>{
                if(data.success){
                    document.getElementById('img-'+id).remove();
                    showMessage(data.message,'success');
                }else{
                    showMessage(data.message,'error');
                }
            })
            .catch(()=>showMessage('Delete failed. Please try again.','error'));
        }
        
        function setMain(id){
            const formData=new FormData();
            formData.append('csrf_token','<?php echo $csrf_token; ?>');
            formData.append('image_id',id);
            formData.append('product_id',<?php echo $product_id; ?>);
            
            fetch('set-main-image.php',{
                method:'POST',
                body:formData
            })
            .then(response=>response.json())
            .then(data=>{
                if(data.success){
                    showMessage(data.message,'success');
                    setTimeout(()=>location.reload(),1500);
                }else{
                    showMessage(data.message,'error');
                }
            })
            .catch(()=>showMessage('Set main failed. Please try again.','error'));
        }
    </script>
</body>
</html> 