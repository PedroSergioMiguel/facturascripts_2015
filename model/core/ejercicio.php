<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\model;

/**
 * Ejercicio contable. Es el periodo en el que se agrupan asientos, facturas, albaranes...
 *
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class ejercicio extends \fs_model
{

    /**
     * Clave primaria. Varchar(4).
     * @var string 
     */
    public $codejercicio;
    public $nombre;
    public $fechainicio;
    public $fechafin;

    /**
     * Estado del ejercicio: ABIERTO|CERRADO
     * @var string 
     */
    public $estado;

    /**
     * ID del asiento de cierre del ejercicio.
     * @var integer 
     */
    public $idasientocierre;

    /**
     * ID del asiento de pérdidas y ganancias.
     * @var integer 
     */
    public $idasientopyg;

    /**
     * ID del asiento de apertura.
     * @var integer 
     */
    public $idasientoapertura;

    /**
     * Identifica el plan contable utilizado. Esto solamente es necesario
     * para dar compatibilidad con Eneboo. En FacturaScripts no se utiliza.
     * @var string 
     */
    public $plancontable;

    /**
     * Longitud de caracteres de las subcuentas asignadas. Esto solamente es necesario
     * para dar compatibilidad con Eneboo. En FacturaScripts no se utiliza.
     * @var integer
     */
    public $longsubcuenta;

    public function __construct($data = FALSE)
    {
        parent::__construct('ejercicios');
        if ($data) {
            $this->codejercicio = $data['codejercicio'];
            $this->nombre = $data['nombre'];
            $this->fechainicio = Date('d-m-Y', strtotime($data['fechainicio']));
            $this->fechafin = Date('d-m-Y', strtotime($data['fechafin']));
            $this->estado = $data['estado'];
            $this->idasientocierre = $this->intval($data['idasientocierre']);
            $this->idasientopyg = $this->intval($data['idasientopyg']);
            $this->idasientoapertura = $this->intval($data['idasientoapertura']);
            $this->plancontable = $data['plancontable'];
            $this->longsubcuenta = $this->intval($data['longsubcuenta']);
        } else {
            $this->codejercicio = NULL;
            $this->nombre = '';
            $this->fechainicio = Date('01-01-Y');
            $this->fechafin = Date('31-12-Y');
            $this->estado = 'ABIERTO';
            $this->idasientocierre = NULL;
            $this->idasientopyg = NULL;
            $this->idasientoapertura = NULL;
            $this->plancontable = '08';
            $this->longsubcuenta = 10;
        }
    }

    protected function install()
    {
        $this->clean_cache();

        return "INSERT INTO " . $this->table_name . " (codejercicio,nombre,fechainicio,fechafin,
         estado,longsubcuenta,plancontable,idasientoapertura,idasientopyg,idasientocierre)
         VALUES ('" . Date('Y') . "','" . Date('Y') . "'," . $this->var2str(Date('01-01-Y')) . ",
         " . $this->var2str(Date('31-12-Y')) . ",'ABIERTO',10,'08',NULL,NULL,NULL);";
    }

    public function abierto()
    {
        return ($this->estado == 'ABIERTO');
    }

    public function year()
    {
        return Date('Y', strtotime($this->fechainicio));
    }

    /**
     * Devuelve un nuevo código para un ejercicio
     * @param string $cod
     * @return string
     */
    public function get_new_codigo($cod = '0001')
    {
        if (!$this->db->select("SELECT * FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($cod) . ";")) {
            return $cod;
        }

        $data = $this->db->select("SELECT MAX(" . $this->db->sql_to_int('codejercicio') . ") as cod FROM " . $this->table_name . ";");
        if (!empty($data)) {
            return sprintf('%04s', (1 + intval($data[0]['cod'])));
        }

        return '0001';
    }

    /**
     * Devuelve la url donde ver/modificar estos datos
     * @return string
     */
    public function url()
    {
        if (is_null($this->codejercicio)) {
            return 'index.php?page=contabilidad_ejercicios';
        }

        return 'index.php?page=contabilidad_ejercicio&cod=' . $this->codejercicio;
    }

    /**
     * Devuelve TRUE si este es el ejercicio predeterminado de la empresa
     * @return type
     */
    public function is_default()
    {
        return ( $this->codejercicio == $this->default_items->codejercicio() );
    }

    /**
     * Devuelve la fecha más próxima a $fecha que esté dentro del intervalo de este ejercicio
     * @param staring $fecha
     * @param boolean $show_error
     * @return string
     */
    public function get_best_fecha($fecha, $show_error = FALSE)
    {
        $fecha2 = strtotime($fecha);

        if ($fecha2 >= strtotime($this->fechainicio) AND $fecha2 <= strtotime($this->fechafin)) {
            return $fecha;
        }

        if ($fecha2 > strtotime($this->fechainicio)) {
            if ($show_error) {
                $this->new_error_msg('La fecha seleccionada está fuera del rango del ejercicio.
               Se ha seleccionado una mejor.');
            }
            return $this->fechafin;
        }

        if ($show_error) {
            $this->new_error_msg('La fecha seleccionada está fuera del rango del ejercicio.
               Se ha seleccionado una mejor.');
        }
        return $this->fechainicio;
    }

    /**
     * Devuelve el ejercicio con codejercicio = $cod
     * @param type $cod
     * @return boolean|\ejercicio
     */
    public function get($cod)
    {
        $data = $this->db->select("SELECT * FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($cod) . ";");
        if ($data) {
            return new \ejercicio($data[0]);
        }

        return FALSE;
    }

    /**
     * Devuelve el ejercicio para la fecha indicada.
     * Si no existe, lo crea.
     * @param string $fecha
     * @param boolean $solo_abierto
     * @param boolean $crear
     * @return boolean|\ejercicio
     */
    public function get_by_fecha($fecha, $solo_abierto = TRUE, $crear = TRUE)
    {
        $sql = "SELECT * FROM " . $this->table_name . " WHERE fechainicio <= "
            . $this->var2str($fecha) . " AND fechafin >= " . $this->var2str($fecha) . ";";

        $data = $this->db->select($sql);
        if ($data) {
            $eje = new \ejercicio($data[0]);
            if ($eje->abierto() OR ! $solo_abierto) {
                return $eje;
            }

            return FALSE;
        } else if ($crear) {
            $eje = new \ejercicio();
            $eje->codejercicio = $eje->get_new_codigo(Date('Y', strtotime($fecha)));
            $eje->nombre = Date('Y', strtotime($fecha));
            $eje->fechainicio = Date('1-1-Y', strtotime($fecha));
            $eje->fechafin = Date('31-12-Y', strtotime($fecha));

            if (strtotime($fecha) < 1) {
                $this->new_error_msg("Fecha no válida: " . $fecha);
            } else if ($eje->save()) {
                return $eje;
            }
        }

        return FALSE;
    }

    /**
     * Devuelve TRUE si el ejercico existe
     * @return boolean
     */
    public function exists()
    {
        if (is_null($this->codejercicio)) {
            return FALSE;
        }

        return $this->db->select("SELECT * FROM " . $this->table_name
                . " WHERE codejercicio = " . $this->var2str($this->codejercicio) . ";");
    }

    /**
     * Comprueba los datos del ejercicio, devuelve TRUE si son correctos
     * @return boolean
     */
    public function test()
    {
        $status = FALSE;

        $this->codejercicio = trim($this->codejercicio);
        $this->nombre = $this->no_html($this->nombre);

        if (!preg_match("/^[A-Z0-9_]{1,4}$/i", $this->codejercicio)) {
            $this->new_error_msg("Código de ejercicio no válido.");
        } else if (strlen($this->nombre) < 1 OR strlen($this->nombre) > 100) {
            $this->new_error_msg("Nombre del ejercicio no válido.");
        } else if (strtotime($this->fechainicio) > strtotime($this->fechafin)) {
            $this->new_error_msg("La fecha de inicio (" . $this->fechainicio . ") es "
                . "posterior a la fecha fin (" . $this->fechafin . ").");
        } else if (strtotime($this->fechainicio) < 1) {
            $this->new_error_msg("Fecha no válida.");
        } else {
            $status = TRUE;
        }

        return $status;
    }

    /**
     * Comprueba más datos del ejercicio, devuelve TRUE si está todo correcto
     * @return boolean
     */
    public function full_test()
    {
        $status = TRUE;

        /// comprobamos la suma de las subcuentas
        if ($this->db->table_exists('co_subcuentas')) {
            $sql = "SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_subcuentas"
                . " WHERE codejercicio = " . $this->var2str($this->codejercicio) . ";";

            $data = $this->db->select($sql);
            if ($data) {
                $debe = floatval($data[0]['debe']);
                $haber = floatval($data[0]['haber']);

                if (!$this->floatcmp($debe, $haber, FS_NF0, TRUE)) {
                    $this->new_error_msg('El ejercicio está descuadrado a nivel de subcuentas.'
                        . ' Debe: ' . $debe . ' | Haber: ' . $haber);
                    $status = FALSE;
                }
            }
        }

        /// comprobamos la suma de las partidas de los asientos
        if ($this->db->table_exists('co_partidas')) {
            $sql = "SELECT SUM(debe) as debe, SUM(haber) as haber FROM co_partidas"
                . " WHERE idasiento IN (SELECT idasiento FROM co_asientos"
                . " WHERE codejercicio = " . $this->var2str($this->codejercicio) . ");";

            $data = $this->db->select($sql);
            if ($data) {
                $debe = floatval($data[0]['debe']);
                $haber = floatval($data[0]['haber']);

                if (!$this->floatcmp($debe, $haber, FS_NF0, TRUE)) {
                    $this->new_error_msg('El ejercicio está descuadrado a nivel de asientos.'
                        . ' Debe: ' . $debe . ' | Haber: ' . $haber);
                    $status = FALSE;
                } else if (!$status) {
                    $this->new_error_msg('Pero <b>NO</b> está descuadrado a nivel de asientos.'
                        . ' Debe: ' . $debe . ' | Haber: ' . $haber);
                }
            }
        }

        return $status;
    }

    /**
     * Guarda los datos en la base de datos
     * @return boolean
     */
    public function save()
    {
        if ($this->test()) {
            $this->clean_cache();

            if ($this->exists()) {
                $sql = "UPDATE " . $this->table_name . " SET nombre = " . $this->var2str($this->nombre)
                    . ", fechainicio = " . $this->var2str($this->fechainicio)
                    . ", fechafin = " . $this->var2str($this->fechafin)
                    . ", estado = " . $this->var2str($this->estado)
                    . ", longsubcuenta = " . $this->var2str($this->longsubcuenta)
                    . ", plancontable = " . $this->var2str($this->plancontable)
                    . ", idasientoapertura = " . $this->var2str($this->idasientoapertura)
                    . ", idasientopyg = " . $this->var2str($this->idasientopyg)
                    . ", idasientocierre = " . $this->var2str($this->idasientocierre)
                    . "  WHERE codejercicio = " . $this->var2str($this->codejercicio) . ";";
            } else {
                $sql = "INSERT INTO " . $this->table_name . " (codejercicio,nombre,fechainicio,fechafin,
               estado,longsubcuenta,plancontable,idasientoapertura,idasientopyg,idasientocierre)
               VALUES (" . $this->var2str($this->codejercicio)
                    . "," . $this->var2str($this->nombre)
                    . "," . $this->var2str($this->fechainicio)
                    . "," . $this->var2str($this->fechafin)
                    . "," . $this->var2str($this->estado)
                    . "," . $this->var2str($this->longsubcuenta)
                    . "," . $this->var2str($this->plancontable)
                    . "," . $this->var2str($this->idasientoapertura)
                    . "," . $this->var2str($this->idasientopyg)
                    . "," . $this->var2str($this->idasientocierre) . ");";
            }

            return $this->db->exec($sql);
        }

        return FALSE;
    }

    /**
     * Elimina el ejercicio
     * @return type
     */
    public function delete()
    {
        $this->clean_cache();
        return $this->db->exec("DELETE FROM " . $this->table_name . " WHERE codejercicio = " . $this->var2str($this->codejercicio) . ";");
    }

    /**
     * Limpiamos la caché
     */
    private function clean_cache()
    {
        $this->cache->delete('m_ejercicio_all');
        $this->cache->delete('m_ejercicio_all_abiertos');
    }

    /**
     * Devuelve un array con todos los ejercicios
     * @return \ejercicio
     */
    public function all()
    {
        /// leemos la lista de la caché
        $listae = $this->cache->get_array('m_ejercicio_all');
        if (empty($listae)) {
            /// si no está en caché, leemos de la base de datos
            $data = $this->db->select("SELECT * FROM " . $this->table_name . " ORDER BY fechainicio DESC;");
            if ($data) {
                foreach ($data as $e) {
                    $listae[] = new \ejercicio($e);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_ejercicio_all', $listae);
        }

        return $listae;
    }

    /**
     * Devuelve un array con todos los ejercicio abiertos
     * @return \ejercicio
     */
    public function all_abiertos()
    {
        /// leemos la lista de la caché
        $listae = $this->cache->get_array('m_ejercicio_all_abiertos');
        if (empty($listae)) {
            /// si no está en caché, leemos de la base de datos
            $sql = "SELECT * FROM " . $this->table_name . " WHERE estado = 'ABIERTO' ORDER BY codejercicio DESC;";
            $data = $this->db->select($sql);
            if ($data) {
                foreach ($data as $e) {
                    $listae[] = new \ejercicio($e);
                }
            }

            /// guardamos la lista en caché
            $this->cache->set('m_ejercicio_all_abiertos', $listae);
        }

        return $listae;
    }
}
