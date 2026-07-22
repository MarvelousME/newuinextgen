<?php
/**
 * Policy gate: hard execution boundaries, global pause, schema policy.
 */

global $ngtai_test_options;

$allowed = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => 'agent.recommend' ] );
ngtai_assert( 'allowed event permitted', 'ALLOW' === $allowed['decision'] );
ngtai_assert( 'allowed event needs no approval', false === $allowed['requires_approval'] );

$payout = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => 'finance.payout.release' ] );
ngtai_assert( 'finance.payout.release denied', 'DENY' === $payout['decision'] );
ngtai_assert( 'payout denial reason is execution boundary', 'execution_action_prohibited' === $payout['reason'] );

foreach ( [ 'finance.refund.execute', 'tutor.approve', 'tutor.reject', 'user.delete', 'deploy.production' ] as $prohibited ) {
	$verdict = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => $prohibited ] );
	ngtai_assert( "prohibited action {$prohibited} denied", 'DENY' === $verdict['decision'] && 'execution_action_prohibited' === $verdict['reason'] );
}

// Global pause blocks even otherwise-allowed events.
update_option( 'ngtai_global_pause', 1 );
$paused = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => 'agent.recommend' ] );
ngtai_assert( 'global pause denies delivery', 'DENY' === $paused['decision'] );
ngtai_assert( 'global pause reason recorded', 'global_pause_active' === $paused['reason'] );
unset( $ngtai_test_options['ngtai_global_pause'] );

$resumed = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => 'agent.recommend' ] );
ngtai_assert( 'delivery resumes after pause cleared', 'ALLOW' === $resumed['decision'] );

// Prohibited action wins even while paused.
update_option( 'ngtai_global_pause', 1 );
$paused_payout = NGTAI_Policy_Gate::evaluate( 'match.requested', [ 'action' => 'finance.payout.release' ] );
ngtai_assert( 'execution boundary checked before pause', 'execution_action_prohibited' === $paused_payout['reason'] );
unset( $ngtai_test_options['ngtai_global_pause'] );

$unknown = NGTAI_Policy_Gate::evaluate( 'made.up.event', [ 'action' => 'agent.recommend' ] );
ngtai_assert( 'unknown event type denied', 'DENY' === $unknown['decision'] && 'external_delivery_not_allowed' === $unknown['reason'] );

$gated = NGTAI_Policy_Gate::evaluate( 'fraud.signal.raised', [ 'action' => 'agent.recommend' ] );
ngtai_assert( 'policy-required event escalates without engine', 'REQUIRE_APPROVAL' === $gated['decision'] );
ngtai_assert( 'escalation flags approval requirement', true === $gated['requires_approval'] );
