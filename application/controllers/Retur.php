<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Retur extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        //load library
        $this->load->library(['template', 'form_validation', 'cart']);
        //load model
        $this->load->model('m_retur');

        header('Last-Modified:' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }

    public function index()
    {
        //cek login
        $this->is_login();
        //kosongkan cart
        $this->cart->destroy();

        $data = [
            'title' => 'Data Retur Barang'
        ];

        $this->template->kasir('retur/index', $data);
    }

    public function tambah_retur()
    {
        $this->is_login();

        //ketika button simpan di klik maka lakukan proses validasi dan penyimpanan data
        if ($this->input->post('submit', TRUE) == 'Submit') {
            //cek apakah user sudah memilih barang atau belum, jika belum maka munculkan pesan kesalahan
            if (!$this->cart->contents()) {
                $this->session->set_flashdata('alert', 'Anda belum memilih barang...');

                redirect('tambah_retur', 'refresh');
            }
            //validasi input data tanggal
            $this->form_validation->set_rules(
                'tanggal',
                'Tanggal Retur',
                'required|callback_checkDateFormat',
                array(
                    'required' => '{field} wajib diisi',
                    'checkDateFormat' => '{field} tidak valid'
                )
            );

            $this->form_validation->set_rules(
                'supplier',
                'Supplier',
                'required|min_length[10]',
                array(
                    'required' => '{field} wajib dipilih',
                    'min_length' => '{field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {

                $id = 'ID' . time();
                $tgl = date('Y-m-d', strtotime(str_replace('/', '-', $this->security->xss_clean($this->input->post('tanggal', TRUE)))));
                $sup = $this->security->xss_clean($this->input->post('supplier', TRUE));
                $user = $this->session->userdata('UserID');

                $data_retur = [
                    'id_retur' => $id,
                    'tgl_retur' => $tgl,
                    'id_supplier' => $sup,
                    'id_user' => $user
                ];
                //baca cart dan memasukkannya dalam array untuk disimpan
                $cart = array();

                foreach ($this->cart->contents() as $c) {
                    $item = [
                        'id_retur' => $id,
                        'id_barang' => $c['id'],
                        'qty' => $c['qty'],
                        'harga' => $c['price']
                    ];

                    //push ke array cart
                    array_push($cart, $item);
                }
                //simpan data retur
                $simpan = $this->m_retur->save('tbl_retur', $data_retur);

                if ($simpan) {
                    //simpan data detail retur
                    $this->m_retur->multiSave('tbl_detail_retur', $cart);
                    //kosongkan cart
                    $this->cart->destroy();
                    //buat notifikasi penyimpanan berhasil
                    $this->session->set_flashdata('success', 'Data retur berhasil ditambahkan...');

                    redirect('data_retur');
                }
            }
        }

        $data = [
            'title' => 'Tambah Data retur',
            'data' => $this->m_retur->getData('tbl_barang', ['active' => 'Y']),
            'supplier' => $this->m_retur->getAllData('tbl_supplier'),
            'table' => $this->read_cart()
        ];

        $this->template->kasir('retur/form_input', $data);
    }

    public function hapus_retur()
    {
        //cek login
        $this->is_login();
        //validasi request ajax
        if ($this->input->is_ajax_request()) {
            //validasi
            $this->form_validation->set_rules(
                'id',
                'ID retur',
                "required|min_length[10]",
                array(
                    'required' => '{field} tidak valid',
                    'min_length' => 'Isi {field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {
                //tangkap rowid
                $id = $this->security->xss_clean($this->input->post('id', TRUE));

                $hapus = $this->m_retur->delete(['tbl_retur', 'tbl_detail_retur'], ['id_retur' => $id]);

                if ($hapus) {
                    echo json_encode(['message' => 'success']);
                } else {
                    echo json_encode(['message' => 'failed']);
                }
            } else {
                echo json_encode(['message' => 'failed']);
            }
        } else {
            redirect('dashboard');
        }
    }

    public function detail_retur($id = null)
    {
        if ($id == null) {
            redirect('data_retur');
        }

        //cek login
        $this->is_login();
        //ambil data
        $getData = $this->m_retur->getDataretur($this->security->xss_clean($id));

        if ($getData->num_rows() < 1) {
            redirect('dashboard');
        }

        $data = [
            'title' => 'Detail Retur ' . $id,
            'data' => $getData
        ];

        $this->template->kasir('retur/detail', $data);
    }

    public function edit_retur($id = null)
    {
        if ($id == null) {
            redirect('data_retur');
        }
        //ambil data retur
        $getData = $this->m_retur->getDataretur($this->security->xss_clean($id));
        //hitung data
        if ($getData->num_rows() < 1) {
            redirect('data_retur');
        }
        //ketika button diklik
        if ($this->input->post('submit', TRUE) == 'Update') {
            //cek apakah user sudah memilih barang atau belum, jika belum maka munculkan pesan kesalahan
            if (!$this->cart->contents()) {
                $this->session->set_flashdata('alert', 'Anda belum memilih barang...');

                redirect('edit_retur/' . $id, 'refresh');
            }
            //validasi input data tanggal
            $this->form_validation->set_rules(
                'tanggal',
                'Tanggal retur',
                'required|callback_checkDateFormat',
                array(
                    'required' => '{field} wajib diisi',
                    'checkDateFormat' => '{field} tidak valid'
                )
            );

            $this->form_validation->set_rules(
                'supplier',
                'Supplier',
                'required|min_length[10]',
                array(
                    'required' => '{field} wajib dipilih',
                    'min_length' => '{field} tidak valid'
                )
            );

            $this->form_validation->set_rules(
                'id',
                'ID retur',
                'required|min_length[10]',
                array(
                    'required' => '{field} wajib diisi',
                    'min_length' => '{field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {

                $id = $this->security->xss_clean($this->input->post('id', TRUE));
                $tgl = date('Y-m-d', strtotime(str_replace('/', '-', $this->security->xss_clean($this->input->post('tanggal', TRUE)))));
                $sup = $this->security->xss_clean($this->input->post('supplier', TRUE));

                $data_retur = [
                    'tgl_retur' => $tgl,
                    'id_supplier' => $sup
                ];

                //baca cart dan memasukkannya dalam array untuk disimpan
                $cart = array();

                foreach ($this->cart->contents() as $c) {
                    $item = [
                        'id_retur' => $id,
                        'id_barang' => $c['id'],
                        'qty' => $c['qty'],
                        'harga' => $c['price']
                    ];

                    //push ke array cart
                    array_push($cart, $item);
                }
                //simpan data retur
                $update = $this->m_retur->update('tbl_retur', $data_retur, ['id_retur' => $id]);

                if ($update) {
                    //hapus detail retur
                    $this->m_retur->delete('tbl_detail_retur', ['id_retur' => $id]);
                    //simpan data detail retur
                    $this->m_retur->multiSave('tbl_detail_retur', $cart);
                    //kosongkan cart
                    $this->cart->destroy();
                    //buat notifikasi penyimpanan berhasil
                    $this->session->set_flashdata('success', 'Data retur berhasil diperbarui...');

                    redirect('data_retur');
                }
            }
        }
        //cek apakah yang akses adalah admin / user yang menginputkan, jika bukan keduanya maka redirect ke halaman data retur
        $fData = $getData->row();

        if ($this->session->userdata('level') != 'admin' && $this->session->userdata('UserID') != $fData->id_user) {
            redirect('data_retur');
        }
        //masukkan detail retur ke cart
        if (!$this->cart->contents()) {
            $dataCart = [];

            foreach ($getData->result() as $c) {
                $keranjang = array(
                    'id'      => $c->kode_barang,
                    'qty'     => $c->qty,
                    'price'   => $c->harga,
                    'name'    => $c->nama_barang
                );

                array_push($dataCart, $keranjang);
            }

            $this->cart->insert($dataCart);
        }

        $data = [
            'title' => 'Edit Data Retur',
            'fdata' => $fData,
            'data' => $this->m_retur->getData('tbl_barang', ['active' => 'Y']),
            'supplier' => $this->m_retur->getAllData('tbl_supplier'),
            'table' => $this->read_cart()
        ];

        $this->template->kasir('retur/form_edit', $data);
    }

    public function tambah_cart()
    {
        //cek login
        $this->is_login();
        //validasi request ajax
        if ($this->input->is_ajax_request()) {
            //validasi data
            $this->form_validation->set_rules(
                'barangx',
                'Barang',
                'required|min_length[3]|max_length[6]',
                array(
                    'required' => '{field} wajib dipilih',
                    'min_length' => 'Isi {field} tidak valid',
                    'max_length' => 'Isi {field} tidak valid',
                )
            );

            $this->form_validation->set_rules(
                'jumlah',
                'Jumlah',
                "required|min_length[1]|regex_match[/^[0-9]+$/]|greater_than[0]",
                array(
                    'required' => '{field} wajib diisi',
                    'min_length' => 'Isi {field} tidak valid',
                    'regex_match' => '{field} hanya boleh angka',
                    'greater_than' => '{field} harus lebih dari nol'
                )
            );

            $this->form_validation->set_rules(
                'harga',
                'Harga Satuan',
                "required|min_length[2]|regex_match[/^[0-9.]+$/]|greater_than[0]",
                array(
                    'required' => '{field} wajib diisi',
                    'min_length' => 'Isi {field} tidak valid',
                    'regex_match' => '{field} hanya boleh angka',
                    'greater_than' => '{field} harus lebih dari nol'
                )
            );

            if ($this->form_validation->run() == TRUE) {
                //ambil barang sesuai kode
                $get_barang = $this->m_retur->getData('tbl_barang', ['kode_barang' => $this->security->xss_clean($this->input->post('barangx', TRUE)), 'active' => 'Y']);

                if ($get_barang->num_rows() == 1) {
                    //fetch data barang dan masukkan kedalam cart
                    $b = $get_barang->row();

                    $keranjang = array(
                        'id'      => $b->kode_barang,
                        'qty'     => $this->security->xss_clean($this->input->post('jumlah', TRUE)),
                        'price'   => $this->security->xss_clean(str_replace('.', '', $this->input->post('harga', TRUE))),
                        'name'    => $b->nama_barang
                    );

                    $this->cart->insert($keranjang);

                    $table = $this->read_cart();

                    $alert = '<div class="alert alert-success" role="alert">Data berhasil ditambahkan ke daftar</div>';

                    $arr = array('table' => $table, 'alert' => $alert, 'status' => 'success');

                    echo json_encode($arr);
                } else {
                    $table = $this->read_cart();

                    $alert = '<div class="alert alert-danger" role="alert">Data barang tidak valid</div>';

                    $arr = array('table' => $table, 'alert' => $alert, 'status' => 'gagal');

                    echo json_encode($arr);
                }
            } else {
                $table = $this->read_cart();

                $alert = '<div class="alert alert-danger" role="alert">' . validation_errors('<p class="mb-0 mt-0"><i class="fa fa-caret-right"></i> ', '</p>') . '</div>';

                $arr = array('table' => $table, 'alert' => $alert, 'status' => 'gagal');

                echo json_encode($arr);
            }
        } else {
            redirect('dashboard');
        }
    }

    public function get_item()
    {
        //cek login
        $this->is_login();
        //validasi request ajax
        if ($this->input->is_ajax_request()) {
            //tangkap rowid
            $rowid = $this->security->xss_clean($this->input->post('rowid', TRUE));

            $get_item = $this->cart->get_item($rowid);

            if ($get_item) {
                $arr = [
                    'barang' => $get_item['id'],
                    'qty' => $get_item['qty'],
                    'harga' => number_format($get_item['price'], 0, ',', '.'),
                    'rowid' => '<input type="hidden" id="rowid" value="' . $get_item['rowid'] . '" />',
                    'table' => $this->read_cart(),
                    'status' => 'true'
                ];
            } else {
                $arr = [
                    'barang' => '',
                    'qty' => '',
                    'harga' => '',
                    'rowid' => '',
                    'table' => $this->read_cart(),
                    'status' => 'false'
                ];
            }

            echo json_encode($arr);
        } else {
            redirect('dashboard');
        }
    }

    public function update_cart()
    {
        //cek login
        $this->is_login();
        //validasi request ajax
        if ($this->input->is_ajax_request()) {
            //validasi data
            $this->form_validation->set_rules(
                'jumlah',
                'Jumlah',
                "required|min_length[1]|regex_match[/^[0-9]+$/]|greater_than[0]",
                array(
                    'required' => '{field} wajib diisi',
                    'min_length' => 'Isi {field} tidak valid',
                    'regex_match' => '{field} hanya boleh angka',
                    'greater_than' => '{field} harus lebih dari nol'
                )
            );

            $this->form_validation->set_rules(
                'harga',
                'Harga Satuan',
                "required|min_length[2]|regex_match[/^[0-9.]+$/]|greater_than[0]",
                array(
                    'required' => '{field} wajib diisi',
                    'min_length' => 'Isi {field} tidak valid',
                    'regex_match' => '{field} hanya boleh angka',
                    'greater_than' => '{field} harus lebih dari nol'
                )
            );

            $this->form_validation->set_rules(
                'rowid',
                'Row ID',
                "required|min_length[10]",
                array(
                    'required' => '{field} tidak valid',
                    'min_length' => 'Isi {field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {

                $keranjang = array(
                    'rowid' => $this->security->xss_clean($this->input->post('rowid', TRUE)),
                    'qty' => $this->security->xss_clean($this->input->post('jumlah', TRUE)),
                    'price' => $this->security->xss_clean(str_replace('.', '', $this->input->post('harga', TRUE)))
                );

                $this->cart->update($keranjang);

                $table = $this->read_cart();

                $alert = '<div class="alert alert-success" role="alert">Data barang berhasil diubah</div>';

                $arr = array('table' => $table, 'alert' => $alert, 'status' => 'success');

                echo json_encode($arr);
            } else {
                $table = $this->read_cart();

                $alert = '<div class="alert alert-danger" role="alert">' . validation_errors('<p class="mb-0 mt-0"><i class="fa fa-caret-right"></i> ', '</p>') . '</div>';

                $arr = array('table' => $table, 'alert' => $alert, 'status' => 'gagal');

                echo json_encode($arr);
            }
        } else {
            redirect('dashboard');
        }
    }

    public function remove_item()
    {
        //cek login
        $this->is_login();
        //validasi request ajax
        if ($this->input->is_ajax_request()) {
            //validasi
            $this->form_validation->set_rules(
                'rowid',
                'Row ID',
                "required|min_length[10]",
                array(
                    'required' => '{field} tidak valid',
                    'min_length' => 'Isi {field} tidak valid'
                )
            );

            if ($this->form_validation->run() == TRUE) {
                //tangkap rowid
                $rowid = $this->security->xss_clean($this->input->post('rowid', TRUE));

                $this->cart->remove($rowid);

                $alert = '<div class="alert alert-success" role="alert">Data barang berhasil dihapus</div>';

                $message = 'success';
            } else {
                $alert = '<div class="alert alert-danger" role="alert">' . validation_errors('<p class="mb-0 mt-0"><i class="fa fa-caret-right"></i> ', '</p>') . '</div>';

                $message = 'failed';
            }

            $table = $this->read_cart();

            $arr = array('table' => $table, 'alert' => $alert, 'message' => $message);

            echo json_encode($arr);
        } else {
            redirect('dashboard');
        }
    }

    function checkDateFormat($date)
    {
        if (preg_match("/^(0[1-9]|[1-2][0-9]|3[0-1])\/(0[1-9]|1[0-2])\/[0-9]{4}$/", $date)) {
            if (checkdate(substr($date, 3, 2), substr($date, 0, 2), substr($date, 6, 4))) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function ajax_retur()
    {
        $this->is_login();
        //cek apakah request berupa ajax atau bukan, jika bukan maka redirect ke home
        if ($this->input->is_ajax_request()) {
            //ambil list data
            $list = $this->m_retur->get_datatables();
            //siapkan variabel array
            $data = array();
            $no = $_POST['start'];

            foreach ($list as $i) {

                $button = '';
                if ($this->session->userdata('level') == 'admin' || $this->session->userdata('UserID') == $i->id_user) :

                    $button .= '<a href="' . site_url('edit_retur/' . $i->id_retur) . '" class="btn btn-warning btn-sm text-white">Edit</a>
                        <button type="button" class="btn btn-danger btn-sm"onclick="hapus_retur(\'' . $i->id_retur . '\')">Hapus</button>';

                endif;

                $no++;
                $row = array();
                $row[] = $no;
                $row[] = $i->id_retur;
                $row[] = $this->tanggal_indo($i->tgl_retur);
                $row[] = $i->nama_supplier;
                $row[] = $i->jumlah;
                $row[] = '<span class="pr-3">' . number_format($i->total, 0, ',', '.') . ',-</span>';
                $row[] = $i->fullname;
                $row[] = '<a href="' . site_url('data_retur/' . $i->id_retur) . '" class="btn btn-sm btn-success">Detail</a>
                ' . $button;

                $data[] = $row;
            }

            $output = array(
                "draw" => $_POST['draw'],
                "recordsTotal" => $this->m_retur->count_all(),
                "recordsFiltered" => $this->m_retur->count_filtered(),
                "data" => $data
            );
            //output to json format
            echo json_encode($output);
        } else {
            redirect('dashboard');
        }
    }

    private function read_cart()
    {
        if ($this->cart->contents()) {

            $table = '';
            $i = 1;
            foreach ($this->cart->contents() as $c) {
                $table .= '<tr><td>' . $i++ . '</td>';
                $table .= '<td>' . $c['name'] . '</td>';
                $table .= '<td class="text-center">' . $c['qty'] . '</td>';
                $table .= '<td class="text-right">' . number_format($c['price'], 0, ',', '.') . '</td>';
                $table .= '<td class="text-right">' . number_format($c['subtotal'], 0, ',', '.') . '</td>';
                $table .= '<td class="text-center">
                                <button type="button" class="btn btn-warning btn-sm text-white" onclick="get_item(\'' . $c['rowid'] . '\')">Edit</button>
                                <button type="button" class="btn btn-danger btn-sm text-white" onclick="remove_item(\'' . $c['rowid'] . '\')">Hapus</button>
                            </td></tr>';
            }
        } else {
            $table = '<tr>
                        <td scope="col" colspan="6" class="text-center"><i>Belum ada data</i></td>
                    </tr>';
        }

        return $table;
    }

    private function tanggal_indo($tgl)
    {
        $bulan  = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        $exp    = explode('-', date('d-m-Y', strtotime($tgl)));

        return $exp[0] . ' ' . $bulan[(int) $exp[1]] . ' ' . $exp[2];
    }

    private function is_login()
    {
        if (!$this->session->userdata('level')) {
            redirect('login');
        }
    }
}
