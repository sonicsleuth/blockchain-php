<?php
/*
 * Title: Block Chain Data Integrity Example in PHP
 * Author: soares.richard@gmail.com
 * Date: March 19, 2019
 * Purpose: A proof of concept showing how to implement Block Chains using PHP and
 * to provide a simple understanding of how the Block Chain concept works to protect
 * the integrity of "blocks" of data.  This example also include the "Proof of Work"
 * requirement which impedes hackers from altering the block data then rehashing the
 * chain - basically it slows down the hashing process of fast computers by using a
 * "difficulty" factor for hashing. This is also known as "Mining".
 */

/**
 * Class Block
 * Creates a single immutable block of data on the chain.
 */
class Block {

    public $index;
    public $timestamp;
    public $data;
    public $previousHash;
    public $hash;
    public $nonce;

    public function __construct($index, $timestamp, $data, $previousHash = '')
    {
        $this->index = $index;                  // Where the block sits on teh chain.
        $this->timestamp = $timestamp;          // When the block was added to the chain.
        $this->data = $data;                    // The data is stored as JSON.
        $this->previousHash = $previousHash;    // Previous hash of the block data.
        $this->hash = $this->calculateHash();   // New hash of the block data.
        $this->nonce = 0;                       // A randomized value in the block when mining a hash.
    }

    // Calculate the hash which represents all the data with the hash from the previous block (ie: chain link).
    public function calculateHash() {
        return hash('sha256', $this->index . $this->previousHash . $this->timestamp . $this->data . $this->nonce);
    }

    // Creates a layer of difficulty when generating a hash. (ie: Slow down fast hacker computers).
    public function mineBlock($difficulty) {
        while(substr($this->hash, 0, $difficulty ) !== str_pad('', $difficulty, '0', STR_PAD_LEFT)) {
            $this->nonce++;
            $this->hash = $this->calculateHash();
        }
    }
}

/**
 * Class BlockChain
 */
class BlockChain {

    public $chain = array();
    public $difficulty = 0;

    public function __construct()
    {
        // When using data stored in a DB, this function would "load" the existing Block Chain data
        // into $this->chain before adding the next block(s) to the chain.
        $this->chain = array($this->createGenesisBlock());

        // Improves the safety of the chain from being altered then rehashed (chained again).
        $this->difficulty = 4;
    }

    // The first Block on the chain is known as the "Genesis Block" and must be set manually.
    private function createGenesisBlock() {
        return new Block(0, "2018-01-01", "Genesis Block", "0");
    }

    // Get the latest data block on the chain.
    public function getLatestBlock() {
        return $this->chain[count($this->chain) - 1];
    }

    // Add a new block of data onto the chain.
    public function addBlock($newBlock) {
        $newBlock->previousHash = $this->getLatestBlock()->hash;
        $newBlock->mineBlock($this->difficulty);
        array_push($this->chain, $newBlock);
    }

    // Inspect the integrity of the entire block chain.
    // This should be performed before adding any new data block onto the existing chain.
    public function isChainValid() {
        for($i = 1; $i < count($this->chain); $i++) { // OK to skip the Genesis block index[0]
            $currentBlock = $this->chain[$i];
            $previousBlock = $this->chain[$i - 1];
            // check integrity of the current block.
            if($currentBlock->hash !== $currentBlock->calculateHash()) {
                return false;
            }
            // check if the current block is linked properly to the previous block.
            if($currentBlock->previousHash !== $previousBlock->hash) {
                return false;
            }
        }
        return true;
    }
}


/* *****************************************************************************************
 * Example Use and Output Follows
 */

// Make a BlockChain
$myBlockChain = new BlockChain();
// Add some data
$myBlockChain->addBlock(new Block(1, "2018-01-02", json_encode(array('user' => 'Tiger Woods', 'amount' => 10))));
$myBlockChain->addBlock(new Block(2, "2018-01-05", json_encode(array('user' => 'John Longsocks', 'amount' => 20))));

// Check Block Chain Integrity
echo "Is Block Chain Valid? ";
if($myBlockChain->isChainValid()) {
    echo 'YES';
    } else {
    echo 'NO';
}
echo "\r\n";
// Dump VALID Block Chain Object
print_r($myBlockChain);

// Hack the planet!
// Change the 'amount' in this block on the chain. (ie: Let's break the integrity of the Block Chain)
$myBlockChain->chain[1]->data = json_encode(array('user' => 'Tiger Woods', 'amount' => 100));
// Check Block Chain Integrity, again.
echo "Is Block Chain Valid? ";
if($myBlockChain->isChainValid()) {
    echo 'YES';
} else {
    echo 'NO';
}
echo "\r\n";
// Dump INVALID Block Chain Object
print_r($myBlockChain);