<?php
session_start();
require_once '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../admin/login.php');
    exit;
}

$product_id = $_GET['product_id'] ?? null;
if (!$product_id) {
    header('Location: admin.php');
    exit;
}

// Get product details
$sql = "SELECT * FROM products WHERE id = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "Product not found!";
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
    $sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$product_id]);
    $existing_images = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .preview{margin:10px 0;padding:8px;border:1px solid #ddd;border-radius:4px;display:inline-block}
        @media(max-width:768px){.grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))}}
    </style>
</head>
<body>
    <div class="container">
        <a href="edit-product.php?id=<?php echo $product_id; ?>" class="btn" style="margin-bottom:20px">← Back to Edit Product</a>
        
        <div class="header">
            <h1>📸 Upload Images</h1>
            <p>Product: <?php echo htmlspecialchars($product['title']); ?></p>
            <p>Current Images: <?php echo count($existing_images); ?></p>
        </div>
        
        <div id="message"></div>
        
        <!-- Upload Section -->
        <div class="section">
            <h3>Upload New Images</h3>
            <form id="uploadForm" enctype="multipart/form-data">
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
                    $filename = !empty($image['filename']) ? $image['filename'] : $image['image_url'];
                    ?>
                    <img src="../assets/img/products/<?php echo htmlspecialchars($filename); ?>" alt="Product image">
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
            
            fetch('delete-image.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`image_id=${id}`
            })
            .then(response=>response.json())
            .then(data=>{
                if(data.success){
                    document.getElementById('img-'+id).remove();
                    showMessage('Image deleted','success');
                }else{
                    showMessage(data.message||'Delete failed','error');
                }
            });
        }
        
        function setMain(id){
            fetch('set-main-image.php',{
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:`image_id=${id}&product_id=<?php echo $product_id; ?>`
            })
            .then(response=>response.json())
            .then(data=>{
                if(data.success){
                    showMessage('Main image updated','success');
                    setTimeout(()=>location.reload(),1000);
                }else{
                    showMessage(data.message||'Update failed','error');
                }
            });
        }
    </script>
</body>
</html> 