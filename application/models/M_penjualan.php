<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Penjualan_model extends CI_Model
{
    public function get_barang()
    {
        $this->db->select('*');
        $this->db->from('tbl_barang');
        $this->db->where('active', 'Y');
        return $this->db->get();
    }

    public function get_barang_detail($kode_barang)
    {
        $this->db->select('*');
        $this->db->from('tbl_barang');
        $this->db->where('kode_barang', $kode_barang);
        $this->db->where('active', 'Y');
        return $this->db->get();
    }
}
?>
