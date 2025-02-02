<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		core/triggers/interface_99_modMyodule_Mytrigger.class.php
 * 	\ingroup	mymodule
 * 	\brief		Sample trigger
 * 	\remarks	You can create other triggers by copying this one
 * 				- File name should be either:
 * 					interface_99_modMymodule_Mytrigger.class.php
 * 					interface_99_all_Mytrigger.class.php
 * 				- The file must stay in core/triggers
 * 				- The class name must be InterfaceMytrigger
 * 				- The constructor method must be named InterfaceMytrigger
 * 				- The name property name must be Mytrigger
 */

/**
 * Trigger class
 */
class InterfaceShip2billWorkflow
{

    private $db;

    /**
     * Constructor
     *
     * 	@param		DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = &$db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "Triggers of this module are empty functions."
            . "They have no effect."
            . "They are provided for tutorial purpose only.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'clinomadic@clinomadic';
    }

    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') {
            return $langs->trans("Development");
        } elseif ($this->version == 'experimental')

                return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else {
            return $langs->trans("Unknown");
        }
    }

    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
        // Users

        global $db,$conf;

        /*
	 *  FACTURE
	 */
        if ($action == 'BILL_VALIDATE' && $conf->global->SHIP2BILL_CLASSYFIED_PAYED_ORDER)
        {
			dol_include_once('/commande/class/commande.class.php');
			dol_include_once('/expedition/class/expedition.class.php');
			dol_include_once('/comm/class/propal.class.php');

			$object->fetchObjectLinked(0,'shipping', $object->id, 'facture');
			if(!empty($object->linkedObjects['shipping'])){
				foreach($object->linkedObjects['shipping'] as $expedition) {
					// Clôturer l'expédition
					if(version_compare(DOL_VERSION, '17.0.0', '>=')){
						$expedition->setBilled();
					} else {
						$expedition->set_billed();
					}

					// Classer facturée la commande si déjà au statut "Délivrée"
					// Ainsi que la proposition rattachée
					$expedition->fetchObjectLinked(0,'commande', $expedition->id, 'shipping');
					if(!empty($expedition->linkedObjects['commande'])){
						$commande = array_pop($expedition->linkedObjects['commande']);
						// Lien commande / facture
						$object->add_object_linked('commande',$commande->id);
						if($commande->statut == 3) {
							$commande->classifyBilled($user);
							$commande->fetchObjectLinked(0,'propal');
							if(!empty($commande->linkedObjects['propal'])){
								$propale = array_pop($commande->linkedObjects['propal']);
								// Lien commande / facture
								$object->add_object_linked('propal',$propale->id);
								$propale->classifyBilled($user);
							}
						}
					}
				}
			}
		}else if($action == 'LINEBILL_DELETE'){
			$res = $object->deleteObjectLinked();
		}else if($action == 'LINEBILL_INSERT'){
			if(($object->origin=='shipping') && !empty($object->origin_id)){
				dol_include_once('/expedition/class/expedition.class.php');
				$shippingline = new ExpeditionLigne($db);
				if(method_exists($shippingline, 'fetch')) {
					$shippingline->fetch($object->origin_id);
					if(!empty($shippingline->fk_expedition))
						$object->add_object_linked($object->origin,$shippingline->fk_expedition);
				}

			}


		}

        return 0;
    }
}
