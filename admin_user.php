<?php
//Gọi cấu hình, thư viện được sử dụng
include 'libs/config.php';
include 'libs/functions.php';

//Khai báo các class sử dụng
use libs\classes\DBAccess;
use libs\classes\FlashMessage;
use libs\classes\DBPagination;
use libs\classes\HttpException;

//Kiểm tra đăng nhập, chưa đăng nhập thì chuyển đến trang đăng nhập
if(!checkAuthentication()) {
	header("Location: admin_login.php");
	exit;
}

//Kiểm tra xem có phải là super admin không
if(getUserAttrSession('is_admin') != 1) {
	header("Location: admin.php");
	exit;
}

//Tạo các đối tượng cần dùng
$oFlashMessage = new FlashMessage();
$oDBAccess = new DBAccess();

//Khai báo tiêu đề và module cho page
$pageAliasName = 'user';
$pageTitle = 'Quản lý Người dùng';

//Kiểm tra xem biến keyword có trên đường link hay không
$keyword = '';
if(isset($_GET['keyword'])) {
	$keyword = $_GET['keyword'];
}
//Tạo ra câu điều kiện theo keyword
$where = '';
if(!empty($keyword)) {
$where = "WHERE username LIKE '%$keyword%' OR fullname LIKE '%$keyword%' OR email LIKE '%$keyword%'";
}

//Thực hiện xử lý xóa bản ghi
if(isset($_GET['action']) && isset($_GET['id']) && $_GET['action']=='delete'){
	try {
		$id = $_GET['id'];
		$oDBAccess->deleteById('user', $id);
		$oFlashMessage->setFlashMessage('success', 'Đã xóa bản ghi có id là '.$id);
	} catch (HttpException $e) {
		$oFlashMessage->setFlashMessage('error', "Không thể xóa bản ghi có id $id được.");
	}

	header("Location: admin_$pageAliasName.php");
	exit;
}

//Thực hiện thay đổi trạng thái bản ghi
if(isset($_GET['action']) && isset($_GET['id']) && $_GET['action']=='active'){
	try {
		$id = $_GET['id'];
		$record = $oDBAccess->findOneById('user', $id);
		$attributes['id'] = $id;
		$attributes['is_active'] = ($record->is_active == 0)?1:0;
		$attributes['updated_at'] = date('Y-m-d H:i:s');
		$record = $oDBAccess->save('user', $attributes, 'id');
		$oFlashMessage->setFlashMessage('success', 'Cập nhật bản ghi có id là '.$id);
	} catch (HttpException $e) {
		$oFlashMessage->setFlashMessage('error', "Không thể cập nhật bản ghi có id $id được.");
	}

	header("Location: admin_$pageAliasName.php");
	exit;
}

//Lấy ra tổng số bản ghi
$totalRecord = intval($oDBAccess->scalarBySQL("SELECT COUNT(*) FROM user ".$where));
//Tạo ra đối tượng phân trang
$oDBPagination = new DBPagination($totalRecord, 10);
//Lấy ra danh sách các bản ghi
$list = $oDBAccess->findAllBySql("SELECT * FROM user $where ORDER BY created_at DESC {$oDBPagination->getLimit()}");
?>

<?php include 'libs/includes/admin/header.inc.php'; ?>

<h2 id="pageTitle"><?= $pageTitle ?></h2>
<p><a href="admin_<?= $pageAliasName ?>_form.php">Thêm mới</a></p>

<?php include "libs/includes/admin/flash_message.inc.php"; ?>

<?php if(!empty($list)): ?>
<div class="search-box m-b-10">
	<form action="" method="GET">
		<a href="admin_<?= $pageAliasName ?>.php"><img src="img/admin/refresh.png" class="v-middle"/></a>
		<input type="image" src="img/admin/search.png" class="v-middle"/>
		<input type="text" name="keyword" value="<?= $keyword ?>" placeholder="Từ khóa..." required aria-required="true" minlength="3" maxlength="255"/>
	</form>
</div><!-- /.search-box -->

<table>
	<thead>
		<tr>
			<th>#</th>
			<th>Họ và tên</th>
			<th>Tài khoản</th>
			<th>Email</th>
			<th>Quyền</th>
			<th>Trạng thái</th>
			<th>Thao tác</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($list as $item): ?>
		<tr>
			<td><?= $item->id ?></td>
			<td><a href="admin_<?= $pageAliasName ?>_form.php?id=<?= $item->id ?>"><?= $item->fullname ?></a></td>
			<td><?= $item->username ?></td>
			<td><?= $item->email ?></td>
			<td class="text-center">
				<?php if($item->is_admin == 1): ?>
				<img src="img/admin/admin.png">
				<?php endif; ?>
			</td>
			<td class="text-center"><?= renderActive($item, 'admin_'.$pageAliasName.'.php') ?></td>
			<td class="text-center">
				<a href="admin_<?= $pageAliasName ?>_form.php?id=<?= $item->id ?>"><img src="img/admin/edit.png"/></a>
				<a href="admin_<?= $pageAliasName ?>.php?action=delete&id=<?= $item->id ?>" class="btn-delete"><img src="img/admin/trash.png"/></a>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>
<?php endif; ?>

<?php if($oDBPagination->getMaxPage() > 1): ?>
<div class="pagination-link">
	<?= $oDBPagination->renderPagination('admin_'.$pageAliasName.'.php') ?>
</div>
<?php endif; ?>

<?php include 'libs/includes/admin/footer.inc.php'; ?>
