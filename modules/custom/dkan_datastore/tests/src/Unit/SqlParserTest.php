<?php

namespace Drupal\dkan_datastore;

use Drupal\dkan_datastore\SqlParser;
use Maquina\StateMachine\MachineOfMachines;
use Maquina\Builder;
use Maquina\Feeder;

use \PHPUnit\Framework\TestCase;

class SqlParserTest extends TestCase
{
  protected $SQLParser;
  protected $StateMachine;

  public function setUp() {
    $this->SQLParser = new SqlParser();
    $this->StateMachine = $this->SQLParser->getSqlMachine();
  }


  public function testSQLParser() {

    $valid_sql_strings = [];
    $valid_sql_strings[] = '[SELECT * FROM abc];';
    $valid_sql_strings[] = '[SELECT abc FROM abc];';
    $valid_sql_strings[] = '[SELECT abc,def FROM abc];';
   //$valid_sql_strings[] = '[SELECT abc, def FROM abc];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij"];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def LIKE "hij"];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs,tuv];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv][LIMIT 1];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv][LIMIT 1 OFFSET 2];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv ASC][LIMIT 1 OFFSET 2];';
    $valid_sql_strings[] = '[SELECT * FROM abc][WHERE def = "hij" AND klm = "nop"][ORDER BY qrs, tuv DESC][LIMIT 1 OFFSET 2];';

    foreach ($valid_sql_strings as $string) {
      $this->StateMachine = $this->SQLParser->getSqlMachine();  //TODO: more elegant way of resetting the State Machine
      $machine = $this->getSqlMachine();
      Feeder::feed($string, $machine);
      $this->assertTrue($machine->isCurrentlyAtAnEndState());
    }

  }

  protected function getSqlMachine() {
    return $this->StateMachine;
  }

}
