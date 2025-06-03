<?php
// File: classes/CustomBuild.php
class CustomBuild
{
  public $buildID, $customerID, $baseModel, $buildDate, $status, $buildCost, $components = [], $pickupDate, $paymentMethod;

  public function __construct($customerID, $baseModel, $components)
  {
    $this->buildID = uniqid('build_');
    $this->customerID = $customerID;
    $this->baseModel = $baseModel;
    $this->buildDate = date('Y-m-d');
    $this->status = 'Pending';
    $this->components = $components;
    $this->calculateCost();
  }

  public function calculateCost()
  {
    $this->buildCost = array_reduce($this->components, fn($sum, $item) => $sum + $item['price'], 0);
  }

  public function updateBuildStatus($newStatus)
  {
    $this->status = $newStatus;
  }

  public function displayBuildDetails()
  {
    echo "Build ID: {$this->buildID}<br>Base: {$this->baseModel}<br>Status: {$this->status}<br>Total: \${$this->buildCost}";
  }

  public function createCustomBuild()
  {
    $builds = json_decode(file_get_contents('data/builds.json'), true) ?: [];
    $builds[] = get_object_vars($this);
    file_put_contents('data/builds.json', json_encode($builds, JSON_PRETTY_PRINT));
  }
}
?>