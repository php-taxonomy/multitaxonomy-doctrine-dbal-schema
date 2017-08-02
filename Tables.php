<?php

namespace PhpTaxonomy\MultiTaxonomy\Doctrine\DBAL;

use Doctrine\DBAL; // Schema\Schema Schema\Table

class Schema
{
    protected $schema;

    function __construct(DBAL\Schema\Schema $schema)
    {
        $this->schema = $schema;
    }

    function TaxoTable() {
        $TaxoTable = $this->schema->createTable("taxonomy_leaf");
        $TaxoTable->addColumn("uuid", "guid");
        $TaxoTable->setPrimaryKey(["uuid"]);
        // $TaxoTable->addColumn("deleted", "datetime", ["notnull" => false]);
        //^ Users should not be able to delete this, probably.
        return $TaxoTable;
    }
    
    function LinkTaxoTaxo(DBAL\Schema\Table $TaxoTable) {
        $LinkTaxoTaxo = $this->schema->createTable("taxonomy_tree");
        $LinkTaxoTaxo->addColumn("uuid", "guid");
        $LinkTaxoTaxo->setPrimaryKey(["uuid"]);
        $LinkTaxoTaxo->addColumn("synonym_uuid", "guid");
        $LinkTaxoTaxo->addIndex(["synonym_uuid"], "taxonomy_tree_synonym");
        $LinkTaxoTaxo->addForeignKeyConstraint($TaxoTable, ["synonym_uuid"], ["uuid"], ["onUpdate" => "CASCADE"], "taxonomy_tree_synonym_uuid_fk");
        $LinkTaxoTaxo->addColumn("parent_uuid", "guid", ["notnull" => false]);
        $LinkTaxoTaxo->addIndex(["parent_uuid"], "taxonomy_tree_parent");
        $LinkTaxoTaxo->addForeignKeyConstraint($TaxoTable, ["parent_uuid"], ["uuid"], ["onUpdate" => "CASCADE"], "taxonomy_tree_parent_uuid_fk");
        // $LinkTaxoTaxo->addUniqueIndex(["parent_id", "synonym_id"], "taxonomy_tree_unique_parent_synonym");
        //^ Optional constraint of unicity.
        $LinkTaxoTaxo->addColumn("term", "string");
        $LinkTaxoTaxo->addIndex(["term"], "taxonomy_term");
        $LinkTaxoTaxo->addUniqueIndex(["parent_uuid","term"], "taxonomy_unique_term");
        //^ Optional constraint of unicity. Useful to maintain usefull relation between terms and ids.
        $LinkTaxoTaxo->addColumn("language", "string", ["length" => 10, "notnull" => false]);
        $LinkTaxoTaxo->addColumn("deleted", "datetime", ["notnull" => false]);
        return $LinkTaxoTaxo;
    }
    
    function LinkTaxonomyUser(DBAL\Schema\Table $LinkTaxoTaxo, $UserTableName) {
        $LinkTaxonomyUser = $this->schema->createTable("link_taxonomy_tree_user");
        $LinkTaxonomyUser->addColumn("uuid", "guid"); // taxonomy encoded with the user uuid
        $LinkTaxonomyUser->setPrimaryKey(["uuid"]);
        $LinkTaxonomyUser->addColumn("taxonomy_tree_uuid", "guid");
        $LinkTaxonomyUser->addIndex(["taxonomy_tree_uuid"], "link_taxonomy_tree_user_taxonomy");
        $LinkTaxonomyUser->addForeignKeyConstraint($LinkTaxoTaxo, ["taxonomy_tree_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "taxonomy_tree_uuid_fk");
        $LinkTaxonomyUser->addColumn("user_uuid", "guid");
        $LinkTaxonomyUser->addIndex(["user_uuid"], "link_taxonomy_tree_user_user");
        $LinkTaxonomyUser->addForeignKeyConstraint($UserTableName, ["user_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "user_uuid_fk");
        $LinkTaxonomyUser->addUniqueIndex(["taxonomy_tree_uuid", "user_uuid"], "link_taxonomy_tree_user_unique_taxonomy_user");
        //^ Optional constraint of unicity, but essential here for link_owned_url_user.
        //^ https://www.postgresql.org/docs/9.2/static/ddl-constraints.html
        //^ TODO: why not a constraint?
        //^ Apparently DBAL supports only one column constaints: http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/schema-representation.html
        $LinkTaxonomyUser->addColumn("deleted", "datetime", ["notnull" => false]);
        // $LinkTaxonomyUser->setPrimaryKey(["url_id", "taxonomy_id"]);
        // BUG: ["onUpdate" => "CASCADE"] seems not supported for sqlite by dbal 2.5.8
        // TODO: schema could be a application ~constant usable from all command objects...
        return $LinkTaxonomyUser;
    }
    
    function LinkUrlTaxo(DBAL\Schema\Table $OwnedUrlTable, DBAL\Schema\Table $TaxoTable) {
        $LinkUrlTaxo = $this->schema->createTable("link_owned_url_taxonomy");
        $LinkUrlTaxo->addColumn("uuid", "guid");
        $LinkUrlTaxo->setPrimaryKey(["uuid"]);
        $LinkUrlTaxo->addColumn("owned_url_uuid", "guid");
        $LinkUrlTaxo->addIndex(["owned_url_uuid"], "link_owned_url_taxonomy_url");
        $LinkUrlTaxo->addForeignKeyConstraint($OwnedUrlTable, ["owned_url_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "owned_url_uuid_fk");
        // $LinkUrlTaxo->addForeignKeyConstraint($OwnedUrlTable, ["url_id"], ["id"], [], "url_id_fk"); changed
        $LinkUrlTaxo->addColumn("taxonomy_uuid", "guid");
        $LinkUrlTaxo->addIndex(["taxonomy_uuid"], "link_owned_url_taxonomy_taxonomy");
        $LinkUrlTaxo->addForeignKeyConstraint($TaxoTable, ["taxonomy_uuid"], ["uuid"], ["onUpdate" => "CASCADE", "onDelete" => "CASCADE"], "taxonomy_uuid_fk");
        // $LinkUrlTaxo->addForeignKeyConstraint($TaxoTable, ["taxonomy_id"], ["id"], [], "taxonomy_id_fk"); // changed
        $LinkUrlTaxo->addUniqueIndex(["owned_url_uuid", "taxonomy_uuid"], "link_owned_url_taxonomy_unique_owned_url_taxonomy");
        //^ Optional constraint of unicity.
        // $LinkUrlTaxo->addColumn("weight", "integer", ["default" => 0]);
        // $LinkUrlTaxo->addColumn("before_id", "integer", ["notnull" => false]);
        // $LinkUrlTaxo->addIndex(["before_id"], "link_url_taxonomy_before");
        // $LinkUrlTaxo->addForeignKeyConstraint($LinkUrlTaxo, ["before_id"], ["id"], ["onUpdate" => "CASCADE"], "before_id_fk");
        // $LinkUrlTaxo->addColumn("after_id", "integer", ["notnull" => false]);
        // $LinkUrlTaxo->addIndex(["after_id"], "link_url_taxonomy_after");
        // $LinkUrlTaxo->addForeignKeyConstraint($LinkUrlTaxo, ["after_id"], ["id"], ["onUpdate" => "CASCADE"], "after_id_fk");
        $LinkUrlTaxo->addColumn("deleted", "datetime", ["notnull" => false]);
        // $LinkUrlTaxo->setPrimaryKey(["url_id", "taxonomy_id"]);
        // BUG: ["onUpdate" => "CASCADE"] seems not supported for sqlite by dbal 2.5.8
        // TODO: schema could be a application ~constant usable from all command objects...
        return $LinkUrlTaxo;
    }
}
    // TODO: consider using postgresql ltree (an array of ltree to allow multiple parents)
    // https://www.postgresql.org/docs/current/static/ltree.html
    //^... How to create an extension. 
    // http://postgresguide.com/cool/hstore.html
    // https://www.postgresql.org/docs/current/static/sql-createextension.html
    // There's both \dx to list installed extensions and \dx+ to list installed and available extensions.
    // https://www.postgresql.org/docs/current/static/arrays.html
