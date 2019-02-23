<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Classes to create relation schema in Dia format.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\Schema\Dia;

use PhpMyAdmin\Plugins\Schema\Eps\TableStatsEps;
use PhpMyAdmin\Plugins\Schema\ExportRelationSchema;
use PhpMyAdmin\Plugins\Schema\Pdf\TableStatsPdf;
use PhpMyAdmin\Plugins\Schema\Svg\TableStatsSvg;
use PhpMyAdmin\Plugins\Schema\Dia\TableStatsDia;
use PhpMyAdmin\Relation;

/**
 * Dia Relation Schema Class
 *
 * Purpose of this class is to generate the Dia XML Document
 * which is used for representing the database diagrams in Dia IDE
 * This class uses Database Table and Reference Objects of Dia and with
 * the combination of these objects actually helps in preparing Dia XML.
 *
 * Dia XML is generated by using XMLWriter php extension and this class
 * inherits ExportRelationSchema class has common functionality added
 * to this class
 *
 * @package PhpMyAdmin
 * @name    Dia_Relation_Schema
 */
class DiaRelationSchema extends ExportRelationSchema
{
    /**
     * @var TableStatsDia[]|TableStatsEps[]|TableStatsPdf[]|TableStatsSvg[]
     */
    private $_tables = array();
    /** @var RelationStatsDia[] Relations */
    private $_relations = array();
    private $_topMargin = 2.8222000598907471;
    private $_bottomMargin = 2.8222000598907471;
    private $_leftMargin = 2.8222000598907471;
    private $_rightMargin = 2.8222000598907471;
    public static $objectId = 0;

    /**
     * The "PhpMyAdmin\Plugins\Schema\Dia\DiaRelationSchema" constructor
     *
     * Upon instantiation This outputs the Dia XML document
     * that user can download
     *
     * @param string $db database name
     *
     * @see Dia,TableStatsDia,RelationStatsDia
     */
    public function __construct($db)
    {
        parent::__construct($db, new Dia());

        $this->setShowColor(isset($_REQUEST['dia_show_color']));
        $this->setShowKeys(isset($_REQUEST['dia_show_keys']));
        $this->setOrientation($_REQUEST['dia_orientation']);
        $this->setPaper($_REQUEST['dia_paper']);

        $this->diagram->startDiaDoc(
            $this->paper,
            $this->_topMargin,
            $this->_bottomMargin,
            $this->_leftMargin,
            $this->_rightMargin,
            $this->orientation
        );

        $alltables = $this->getTablesFromRequest();

        foreach ($alltables as $table) {
            if (!isset($this->tables[$table])) {
                $this->_tables[$table] = new TableStatsDia(
                    $this->diagram,
                    $this->db,
                    $table,
                    $this->pageNumber,
                    $this->showKeys,
                    $this->offline
                );
            }
        }

        $seen_a_relation = false;
        foreach ($alltables as $one_table) {
            $exist_rel = $this->relation->getForeigners($this->db, $one_table, '', 'both');
            if (!$exist_rel) {
                continue;
            }

            $seen_a_relation = true;
            foreach ($exist_rel as $master_field => $rel) {
                /* put the foreign table on the schema only if selected
                 * by the user
                 * (do not use array_search() because we would have to
                 * to do a === false and this is not PHP3 compatible)
                 */
                if ($master_field != 'foreign_keys_data') {
                    if (in_array($rel['foreign_table'], $alltables)) {
                        $this->_addRelation(
                            $one_table,
                            $master_field,
                            $rel['foreign_table'],
                            $rel['foreign_field'],
                            $this->showKeys
                        );
                    }
                    continue;
                }

                foreach ($rel as $one_key) {
                    if (!in_array($one_key['ref_table_name'], $alltables)) {
                        continue;
                    }

                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $this->_addRelation(
                            $one_table,
                            $one_field,
                            $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index],
                            $this->showKeys
                        );
                    }
                }
            }
        }
        $this->_drawTables();

        if ($seen_a_relation) {
            $this->_drawRelations();
        }
        $this->diagram->endDiaDoc();
    }

    /**
     * Output Dia Document for download
     *
     * @return void
     * @access public
     */
    public function showOutput()
    {
        $this->diagram->showOutput($this->getFileName('.dia'));
    }

    /**
     * Defines relation objects
     *
     * @param string $masterTable  The master table name
     * @param string $masterField  The relation field in the master table
     * @param string $foreignTable The foreign table name
     * @param string $foreignField The relation field in the foreign table
     * @param bool   $showKeys     Whether to display ONLY keys or not
     *
     * @return void
     *
     * @access private
     * @see    TableStatsDia::__construct(),RelationStatsDia::__construct()
     */
    private function _addRelation(
        $masterTable,
        $masterField,
        $foreignTable,
        $foreignField,
        $showKeys
    ) {
        if (!isset($this->_tables[$masterTable])) {
            $this->_tables[$masterTable] = new TableStatsDia(
                $this->diagram,
                $this->db,
                $masterTable,
                $this->pageNumber,
                $showKeys
            );
        }
        if (!isset($this->_tables[$foreignTable])) {
            $this->_tables[$foreignTable] = new TableStatsDia(
                $this->diagram,
                $this->db,
                $foreignTable,
                $this->pageNumber,
                $showKeys
            );
        }
        $this->_relations[] = new RelationStatsDia(
            $this->diagram,
            $this->_tables[$masterTable],
            $masterField,
            $this->_tables[$foreignTable],
            $foreignField
        );
    }

    /**
     * Draws relation references
     *
     * connects master table's master field to
     * foreign table's foreign field using Dia object
     * type Database - Reference
     *
     * @return void
     *
     * @access private
     * @see    RelationStatsDia::relationDraw()
     */
    private function _drawRelations()
    {
        foreach ($this->_relations as $relation) {
            $relation->relationDraw($this->showColor);
        }
    }

    /**
     * Draws tables
     *
     * Tables are generated using Dia object type Database - Table
     * primary fields are underlined and bold in tables
     *
     * @return void
     *
     * @access private
     * @see    TableStatsDia::tableDraw()
     */
    private function _drawTables()
    {
        foreach ($this->_tables as $table) {
            $table->tableDraw($this->showColor);
        }
    }
}
