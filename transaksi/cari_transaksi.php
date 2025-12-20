<?php
include_once('../header.php');
$id = @$_GET['id'];
$sql = $koneksi->query("SELECT * FROM transaksi WHERE id_transaksi='$id'");
?>

<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Transaksi</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Filter Transaksi</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Filter Transaksi</h4>

                        <div class="table-responsive">
							<div class="float-right">
							<a href="<?=base_url('laporan/cetak_filter.php')?>" class="btn btn-secondary btn-sm" target="_blank"><i class="fa fa-print"></i> Cetak</a>
								<a href="<?=base_url('transaksi')?>" class="btn btn-warning btn-sm"><i class="fa fa-arrow-left"></i> Kembali</a>
							</div>
							<div class="float-left">
								<form action="#" method="POST">
									<table>
										<tr>
											<td>
												<div class="input-group">
													<span class="input-group-text" id="basic-addon1">Dari</span>
													<input type="date" class="form-control" name="tgl1">
												</div>
											</td>
											<td>
												<div class="input-group">
													<span class="input-group-text" id="basic-addon1">Sampai</span>
													<input type="date" class="form-control" name="tgl2">
												</div>
											</td>
											<td>
												<input type="submit" value="Filter" name="cari"
													class="form-control btn btn-success btn-sm">
											</td>

										</tr>
									</table>
								</form>
								<?php
									//proses jika sudah klik tombol pencarian data
									if(isset($_POST['cari'])){
									//menangkap nilai form
									$tgl1 =$_POST['tgl1'];
									$tgl2 =$_POST['tgl2'];
									if(empty($tgl1) || empty($tgl2)){
									//jika data tanggal kosong
									?>
								<script language="JavaScript">
									Swal.fire({
										icon: 'warning',
										title: 'Peringatan!',
										text: 'Tanggal Awal dan Tanggal Akhir Harap Diisi',
										confirmButtonColor: '#ffc107',
										confirmButtonText: 'OK'
									}).then(() => {
										window.location.href = "?page=transaksi&aksi=cari";
									});
								</script>
								<?php
									} else {
									?>
								<i><b>Informasi : </b> Hasil pencarian data berdasarkan periode Tanggal
									<b><?php echo $_POST['tgl1']?></b> s/d <b><?php echo $_POST['tgl2']?></b></i>
								<?php
									$sql = $koneksi->query("SELECT * FROM transaksi JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar WHERE tgl BETWEEN '$tgl1' AND '$tgl2' ORDER BY tgl ASC");
									}
									}
									?>
							</div><br><br><br>
							<table class="table table-bordered table-hover table-striped" id="">
								<thead>
									<tr>
										<th style="width: 5%">No.</th>
										<th>Tanggal</th>
										<th>ID Pelanggan</th>
										<th>Nama Pelanggan</th>
										<th>Jenis Pembayaran</th>
										<th>Harga</th>
										<th>Status</th>
									</tr>

								</thead>
								<tbody>
									<?php
										$no = 1;

										while($data = $sql->fetch_assoc()) {
											$tgl = $data['tgl'];
											$status = ($data['status'] == 'Lunas')? "<span class='badge badge-pill badge-success'>Lunas</span>" : "<span class='badge badge-pill badge-danger'>Belum Bayar</span>";
										?>
									<tr>
										<td><?=$no++."."; ?></td>
										<td><?=date('d/m/Y', strtotime($tgl));?></td>
										<td><?=$data['idpel'];?></td>
										<td><?=$data['nama'];?></td>
										<td><?=$data['jenis_bayar'];?></td>
										<td>Rp. <?=number_format($data['harga'], 0, ",", ".");?></td>
										<td><?=$status?></td>

									</tr>
									<?php
										}
										?>
								</tbody>
							</table>
						</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include_once('../footer.php');
    ?>
</body>
</html>

