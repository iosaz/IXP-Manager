<?php

use Entities\Switcher;
/*
 * Copyright (C) 2009-2011 Internet Neutral Exchange Association Limited.
 * All Rights Reserved.
 *
 * This file is part of IXP Manager.
 *
 * IXP Manager is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, version v2.0 of the License.
 *
 * IXP Manager is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License v2.0
 * along with IXP Manager.  If not, see:
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */


/**
 * Controller: Manage Interfaces
 *
 * @author     Barry O'Donovan <barry@opensolutions.ie>
 * @category   IXP
 * @package    IXP_Controller
 * @copyright  Copyright (c) 2009 - 2012, Internet Neutral Exchange Association Ltd
 * @license    http://www.gnu.org/licenses/gpl-2.0.html GNU GPL V2.0
 */
class InfrastructureController extends IXP_Controller_FrontEnd
{
    /**
     * This function sets up the frontend controller
     */
    protected function _feInit()
    {
        $this->view->feParams = $this->_feParams = (object)[
            'entity'        => '\\Entities\\Infrastructure',
            'form'          => 'IXP_Form_Infrastructure',
            'pagetitle'     => 'Infrastructures',

            'titleSingular' => 'Infrastructure',
            'nameSingular'  => 'an infrastructure',

            'listOrderBy'    => 'name',
            'listOrderByDir' => 'ASC'
        ];

        switch( $this->getUser()->getPrivs() )
        {
            case \Entities\User::AUTH_SUPERUSER:
                $this->_feParams->listColumns = [
                    'id'        => [ 'title' => 'UID', 'display' => false ],
                    'name'      => 'Name',
                    'shortname' => 'Shortname',
                    'ixp_name'  => 'IXP'
                ];

                // display the same information in the view as the list
                $this->_feParams->viewColumns = $this->_feParams->listColumns;

                $this->_feParams->defaultAction = 'list';
                break;

            default:
                $this->redirectAndEnsureDie( 'error/insufficient-permissions' );
        }
    }

    /**
     * Provide array of users for the listAction and viewAction
     *
     * @param int $id The `id` of the row to load for `viewAction`. `null` if `listAction`
     */
    protected function listGetData( $id = null )
    {
        $qb = $this->getD2EM()->createQueryBuilder()
            ->select( 'i.id AS id, i.name AS name,
                i.shortname AS shortname, ix.shortname AS ixp_name,
                ix.id AS ixp_id'
            )
            ->from( '\\Entities\\Infrastructure', 'i' )
            ->leftJoin( 'i.IXP', 'ix' );

        if( isset( $this->_feParams->listOrderBy ) )
            $qb->orderBy( $this->_feParams->listOrderBy, isset( $this->_feParams->listOrderByDir ) ? $this->_feParams->listOrderByDir : 'ASC' );

        if( $id !== null )
            $qb->andWhere( 'i.id = ?1' )->setParameter( 1, $id );

        return $qb->getQuery()->getResult();
    }

    /**
     * Pre db flush hook that can be overridden by subclasses for add and edit.
     *
     * This is called if the user POSTs a valid form after the posted
     * data has been assigned to the object and just before it is (persisted
     * if adding) and the database is flushed.
     *
     * This hook can prevent flushing by returning false.
     *
     * **NB: You should not `flush()` here unless you know what you are doing**
     *
     * A call to `flush()` is made after this method returns true ensuring a
     * transactional `flush()` for all.
     *
     * @param OSS_Form $form The Send form object
     * @param \Entities\Infrastructure $object The Doctrine2 entity (being edited or blank for add)
     * @param bool $isEdit True if we are editing, otherwise false
     * @return bool If false, the form is not persisted
     */
    protected function addPreFlush( $form, $object, $isEdit )
    {
        $ixp = $this->getD2R( '\\Entities\\IXP' )->find( $form->getValue( 'ixp' ) );
        
        if( !$ixp )
        {
            $this->addMessage( 'Culd not load requested object', OSS_Message::ERROR );
            $thos->redirectAndEnsureDie( '/infrastructure' );
        }
        $object->setIXP( $ixp );
        
        return true;
    }


}
