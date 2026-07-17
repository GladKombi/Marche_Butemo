<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
function paymentResponse(bool $ok,string $message,int $status=200):void{http_response_code($status);echo json_encode(['success'=>$ok,'message'=>$message],JSON_UNESCAPED_UNICODE);exit;}
if(empty($_SESSION['is_logged_in'])||($_SESSION['user_type']??'')!=='agriculteur')paymentResponse(false,'Accès non autorisé.',403);
require_once __DIR__.'/../../config/database.php';
$data=json_decode(file_get_contents('php://input'),true)?:[];$id=filter_var($data['id']??null,FILTER_VALIDATE_INT);
if(($data['action']??'')!=='confirm'||!$id)paymentResponse(false,'Paiement invalide.',422);
$owned=fetchOne("SELECT pa.id,pa.date_paiement FROM paiements pa JOIN ligne_commandes lc ON lc.commande_id=pa.commande_id AND lc.supprime=0 JOIN produits p ON p.id=lc.produit_id AND p.supprime=0 JOIN agriculteurs a ON a.id=p.agriculteur_id AND a.supprime=0 WHERE pa.id=:id AND pa.supprime=0 AND a.utilisateur_id=:user LIMIT 1",[':id'=>$id,':user'=>(int)$_SESSION['user_id']]);
if(!$owned)paymentResponse(false,'Ce paiement ne concerne pas vos produits.',404);
if($owned->date_paiement)paymentResponse(false,'Ce paiement est déjà confirmé.',409);
$result=executeQuery('UPDATE paiements SET date_paiement=NOW() WHERE id=:id AND date_paiement IS NULL',[':id'=>$id]);
paymentResponse((bool)$result,$result?'Paiement confirmé avec succès.':'Impossible de confirmer le paiement.',$result?200:500);
