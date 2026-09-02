---
schema: kumwe-migration-handoff/v2
artifact_kind: framework_php
migration_id: KUMWE-MIG-2026-001
change_set: KUMWE-CS-2026-001
state: draft_pr_open
source:
  app:
    repository: https://github.com/kumwe/app
    baseline_commit: "6f9e42cb59a84ba3ca523a70475cf4d7263c68e7"
    examined_paths:
      - src/Application/Persistence/TransactionManager.php
      - src/Application/Persistence/TransactionState.php
      - src/Infrastructure/Persistence/DoctrineTransactionManager.php
      - src/Infrastructure/Persistence/DoctrineTransactionState.php
      - src/Kernel/ContainerFactory.php
      - src
      - tests/Support/ImmediateTransactionManager.php
      - tests/Architecture/TransactionSeamBoundaryTest.php
      - tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php
      - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
      - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
      - tests
      - config
      - bootstrap
      - examples
      - composer.json
      - composer.lock
      - docs/architecture/layers.json
      - docs/architecture/capability-index.md
      - docs/architecture/governance/core-growth-baseline.json
      - docs/architecture/governance/legacy-packages.json
      - vendor/kumwe/conversion
      - vendor/kumwe/extension-sdk
      - vendor/kumwe/producer
    old_namespace_roots:
      - Kumwe\App\Application\Persistence\
    capability_index_sha256: "87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39"
  semantic_inputs: []
  examined_dependencies:
    - "kumwe/conversion 0.1.2 (installed, legacy-unmanifested); owns conversion semantics, no transaction port"
    - "kumwe/extension-sdk 0.2.4 (installed, legacy-unmanifested); extension contracts, no transaction port"
    - "kumwe/producer 0.2.0 (installed, legacy-unmanifested); a host-atomic mutation boundary, not a transaction port"
    - "no Kumwe dependency selected; the allowed ceiling is None and the runtime requirement is php ^8.5 alone"
  active_related_pull_requests: []
target:
  repository: https://github.com/kumwe/transaction
  artifact_identity: "kumwe/transaction (Composer library)"
  canonical_namespace_or_abi: Kumwe\Transaction
  branch: phase-1/extract-transaction
  pull_request: "https://github.com/kumwe/transaction/pull/1"
ownership:
  responsibility: "Storage-neutral transaction port: atomic scopes, settlement hooks and an open-transaction view."
  non_responsibilities:
    - "Any transaction implementation: driver adapters, connections, savepoints and isolation levels stay in the host."
    - "Nested-transaction, retry, deadlock and timeout policy."
    - "Logging, audit, outbox and event coordination around a transaction."
    - "Container registration: the host binds its adapter to the contract identifiers; there is no provider."
    - "Real-database evidence, which the host proves on the engines it supports."
  allowed_dependency_ceiling: []
  implementation_owner: kumwe/transaction
  next_consumer: kumwe/app
  public_manifests:
    - path: resources/public-api/v1.json
      sha256: "19b82875a1bb58ab6862cf5ac22fd789acd28035113743464dd73c4131789b00"
    - path: resources/capabilities/v1.json
      sha256: "b2230b7bf40bf0e6969c527babb4c5880759ee511053efcded9abcf5d31180f7"
    - path: resources/service-map/v1.json
      sha256: "37ebd623d4e862057e20425c7e81c0dc4d64a59e7e06a0ff6c863aca37c96f12"
  intentionally_excluded:
    - "DoctrineTransactionManager stays in App; it owns the DBAL connection and the nesting policy"
    - "DoctrineTransactionState stays in App; it reads the DBAL connection"
    - "The two share() bindings in src/Kernel/ContainerFactory.php stay in App; composition is host authority"
    - "The App's inline scope-recording test doubles stay in App; they are test-local probes, not package behaviour"
    - "Retry, deadlock, timeout, logging, audit and outbox coordination stay in App; they are policy around the port"
framework_php:
  composer_package: kumwe/transaction
  canonical_namespace: Kumwe\Transaction
  public_api_manifest: resources/public-api/v1.json
  capability_manifest: resources/capabilities/v1.json
  service_map: resources/service-map/v1.json
  extracted_symbols:
    - old_fqcn: Kumwe\App\Application\Persistence\TransactionManager
      new_fqcn: Kumwe\Transaction\Contract\TransactionManager
      source_path: src/Application/Persistence/TransactionManager.php
      target_path: src/Contract/TransactionManager.php
      kind: interface
      public_methods:
        - transactional
        - afterCommit
        - afterRollback
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: preserved
    - old_fqcn: Kumwe\App\Application\Persistence\TransactionState
      new_fqcn: Kumwe\Transaction\Contract\TransactionState
      source_path: src/Application/Persistence/TransactionState.php
      target_path: src/Contract/TransactionState.php
      kind: interface
      public_methods:
        - isActive
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: preserved
    - old_fqcn: Kumwe\App\Tests\Support\ImmediateTransactionManager
      new_fqcn: Kumwe\Transaction\Testing\ImmediateTransactionManager
      source_path: tests/Support/ImmediateTransactionManager.php
      target_path: src/Testing/ImmediateTransactionManager.php
      kind: class
      public_methods:
        - transactional
        - afterCommit
        - afterRollback
      public_properties: []
      public_constants: []
      exceptions: []
      serialization_contract: null
      compatibility: "preserved; declared final readonly instead of final, no behavioural change"
  consumers:
    app_code:
      - src/Administrator/Http/Handler/AdministratorAccessControlHandler.php
      - src/Administrator/Http/Handler/AdministratorBusinessSecurityHandler.php
      - src/Application/Authorization/ResourceOwnershipScopeService.php
      - src/Application/Authorization/SiteGroupAdministration.php
      - src/Application/Automation/AutomationManagementService.php
      - src/Application/Presentation/Preference/PresentationPreferenceManager.php
      - src/Audit/Infrastructure/Persistence/DoctrineAuditAnchorWriter.php
      - src/Audit/Infrastructure/Persistence/DoctrineAuditRetentionService.php
      - src/Audit/Infrastructure/Persistence/DoctrineAuditTrailExporter.php
      - src/BusinessDefinition/Application/BusinessDefinitionService.php
      - src/BusinessIntegration/Application/IntegrationEventConsumerDispatcher.php
      - src/BusinessIntegration/Application/IntegrationOperationsService.php
      - src/BusinessIntegration/Application/ProcessWorkDispatcher.php
      - src/BusinessIntegration/Infrastructure/ContributedScheduleSynchronizer.php
      - src/BusinessIntegration/Infrastructure/DoctrineInboxStore.php
      - src/BusinessIntegration/Infrastructure/DoctrineOutboxStore.php
      - src/BusinessIntegration/Infrastructure/DoctrineProcessManagerStore.php
      - src/BusinessRecord/Application/BusinessRecordIdempotencyPurger.php
      - src/BusinessRecord/Application/BusinessRecordService.php
      - src/BusinessRecord/Application/PostingPeriodService.php
      - src/BusinessRecord/Infrastructure/Persistence/DoctrineRecordSecretRotation.php
      - src/BusinessReporting/Application/ExportAttemptPublisher.php
      - src/BusinessReporting/Application/ExportGenerationService.php
      - src/BusinessReporting/Application/ExportService.php
      - src/BusinessReporting/Infrastructure/DoctrineExportArtifactRepository.php
      - src/BusinessReporting/Infrastructure/DoctrineProjectionRuntime.php
      - src/BusinessReporting/Infrastructure/DoctrineProjectionStore.php
      - src/BusinessReporting/Infrastructure/FilesystemExportArtifactRepository.php
      - src/BusinessSchema/Application/BusinessSchemaExecutor.php
      - src/BusinessSchema/Application/BusinessSchemaPlanner.php
      - src/BusinessSchema/Application/BusinessSchemaService.php
      - src/BusinessSecurity/Application/Administration/BusinessSecurityAdministrationService.php
      - src/BusinessSecurity/Application/Approval/ApprovalService.php
      - src/BusinessSurface/Application/BusinessMutationPlanService.php
      - src/BusinessSurface/Application/BusinessOperationStatusService.php
      - src/BusinessSurface/Application/BusinessSurfaceCatalog.php
      - src/BusinessSurface/Application/BusinessSurfaceService.php
      - src/BusinessSurface/Application/CustomBusinessActionExecutor.php
      - src/BusinessSurface/Application/GeneratedBusinessActionStepUp.php
      - src/Content/Application/ContentModelService.php
      - src/Content/Application/ContentService.php
      - src/Delivery/Http/Api/Idempotency/PersistentIdempotencyMiddleware.php
      - src/Delivery/Http/Api/Idempotency/SecretOnceIdempotencyMiddleware.php
      - src/Demo/Infrastructure/DemoAccessProvisioner.php
      - src/Demo/Infrastructure/DemoContentProfileInstaller.php
      - src/Demo/Infrastructure/VdmBusinessDemoInstaller.php
      - src/Extension/Application/Trust/TrustStore.php
      - src/Extension/Infrastructure/DoctrineExtensionManager.php
      - src/Identity/Application/Administration/AccessControlService.php
      - src/Identity/Application/StepUp/TotpStepUpProvider.php
      - src/Identity/Infrastructure/Administration/DoctrineAdministratorIdentityGateway.php
      - src/Identity/Infrastructure/Administration/DoctrineAdministratorSessionStore.php
      - src/Infrastructure/Automation/DoctrineJobQueue.php
      - src/Infrastructure/Automation/DoctrineQueueRuntimeOperations.php
      - src/Infrastructure/Automation/DoctrineScheduler.php
      - src/Infrastructure/Mcp/McpMutationGuard.php
      - src/Infrastructure/Persistence/DoctrineTransactionManager.php
      - src/Infrastructure/Persistence/DoctrineTransactionState.php
      - src/Infrastructure/Persistence/Migration/MigrationRunner.php
      - src/Localization/Application/MessageOverrideService.php
      - src/Media/Application/MediaService.php
      - src/Navigation/Application/NavigationService.php
      - src/Portal/Http/Handler/PortalApprovalHandler.php
      - src/Portal/Infrastructure/Session/DoctrinePortalSessionStore.php
      - src/Presentation/Infrastructure/DoctrineAdministratorThemeRecovery.php
      - src/Site/Infrastructure/Persistence/DoctrineSiteSettings.php
      - src/Studio/Application/Composition/StudioContentCompositionService.php
      - src/Studio/Application/Host/StudioProducerHostFactory.php
      - src/Studio/Application/Host/StudioProducerMutationBoundary.php
      - src/Studio/Application/Media/StudioMediaService.php
    configuration_and_di:
      - src/Kernel/ContainerFactory.php
    reflection_and_string_references:
      - "tests/Architecture/TransactionSeamBoundaryTest.php (asserts the old namespace prefix and port directory)"
      - "docs/architecture/governance/core-growth-baseline.json (baseline entries for both old FQCNs)"
    fixtures_and_examples:
      - tests/Architecture/TransactionSeamBoundaryTest.php
      - tests/Integration/Automation/AutomationManagementIntegrationTest.php
      - tests/Integration/BusinessIntegration/PoisonAndDeadLetterIntegrationTest.php
      - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
      - tests/Integration/BusinessRecord/BusinessRecordHistoryPagingIntegrationTest.php
      - tests/Integration/BusinessRecord/BusinessRecordInverseRelationshipIntegrationTest.php
      - tests/Integration/BusinessRecord/BusinessRecordRelationshipIntegrationTest.php
      - tests/Integration/BusinessRecord/BusinessRecordRuntimeIntegrationTest.php
      - tests/Integration/BusinessSchema/BusinessSchemaExecutionStateGuardIntegrationTest.php
      - tests/Integration/BusinessSchema/BusinessSchemaRecoveryIntegrationTest.php
      - tests/Integration/BusinessSurface/GeneratedBusinessQueryBudgetIntegrationTest.php
      - tests/Integration/Localization/MessageOverrideIntegrationTest.php
      - tests/Integration/Mcp/McpMutationGuardIntegrationTest.php
      - tests/Integration/Mcp/McpTrustLifecycleIntegrationTest.php
      - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
      - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
      - tests/Integration/Studio/StudioArtifactRecoveryVectorReplayIntegrationTest.php
      - tests/Support/ImmediateTransactionManager.php
      - tests/Support/NeutralBusinessFixture.php
      - tests/Support/TestKernelFactory.php
      - tests/Support/TransientBusinessDefinitionFixtureScope.php
      - tests/Support/prepare-browser-contribution.php
      - tests/Unit/Administrator/Http/Handler/AdministratorAccessControlHandlerTest.php
      - tests/Unit/Administrator/Http/Handler/AdministratorContentEditorRetentionTest.php
      - tests/Unit/Administrator/Http/Handler/AdministratorDashboardHandlerTest.php
      - tests/Unit/Administrator/Http/Handler/AdministratorStudioHostHandlerTest.php
      - tests/Unit/Application/Authorization/AdapterAuthorizationParityTest.php
      - tests/Unit/Application/Authorization/ApplicationAuthorizationTest.php
      - tests/Unit/Application/Authorization/ResourceOwnershipScopeServiceTest.php
      - tests/Unit/Application/Authorization/SiteGroupAdministrationTest.php
      - tests/Unit/Application/Automation/AutomationManagementServiceTest.php
      - tests/Unit/Application/Automation/PurgeBusinessRecordIdempotencyHandlerTest.php
      - tests/Unit/BusinessIntegration/ConsumerDispatcherTest.php
      - tests/Unit/BusinessIntegration/Infrastructure/RuntimeIntegrationEventTransportTest.php
      - tests/Unit/BusinessIntegration/IntegrationOperationsServiceTest.php
      - tests/Unit/BusinessIntegration/ProcessWorkDispatcherTest.php
      - tests/Unit/BusinessRecord/Application/PostingPeriodServiceTest.php
      - tests/Unit/BusinessReporting/ExportGenerationPolicyFenceTest.php
      - tests/Unit/BusinessReporting/ExportServiceTransactionTest.php
      - tests/Unit/BusinessReporting/RecordExportPipelineTest.php
      - tests/Unit/BusinessSecurity/Application/ApprovalServiceTest.php
      - tests/Unit/BusinessSecurity/Application/BusinessSecurityAdministrationServiceTest.php
      - tests/Unit/BusinessSurface/Application/BusinessMutationPlanServiceTest.php
      - tests/Unit/BusinessSurface/Application/BusinessSurfaceCatalogTest.php
      - tests/Unit/BusinessSurface/Application/BusinessSurfaceServiceTest.php
      - tests/Unit/BusinessSurface/Application/Custom/CustomBusinessActionExecutorTest.php
      - tests/Unit/BusinessSurface/Application/GeneratedBusinessActionStepUpTest.php
      - tests/Unit/Content/Application/ContentTranslationServiceTest.php
      - tests/Unit/Content/Application/ContributedContentTranslationTest.php
      - tests/Unit/Content/Presentation/TranslationGroupPresenterTest.php
      - tests/Unit/Delivery/Console/Command/DemoInstallCommandTest.php
      - tests/Unit/Delivery/Console/Command/ManagePostingPeriodsCommandTest.php
      - tests/Unit/Delivery/Http/Api/Automation/AutomationApiHandlerTest.php
      - tests/Unit/Delivery/Http/Api/Business/BusinessOperationStatusApiHandlerTest.php
      - tests/Unit/Delivery/Http/Api/Business/BusinessRecordApiHandlerTest.php
      - tests/Unit/Delivery/Http/Api/Business/PostingPeriodApiHandlerTest.php
      - tests/Unit/Delivery/Http/Api/Idempotency/PersistentIdempotencyAuthorizationTest.php
      - tests/Unit/Delivery/Http/Api/Idempotency/PersistentIdempotencyMiddlewareTest.php
      - tests/Unit/Demo/Infrastructure/DemoExampleExtensionInstallerTest.php
      - tests/Unit/Demo/Infrastructure/VdmBusinessDemoInstallerTest.php
      - tests/Unit/Extension/Application/Trust/TrustStoreTest.php
      - tests/Unit/Extension/Runtime/TrustEnforcingJobHandlerTest.php
      - tests/Unit/Http/Handler/HomePageHandlerTest.php
      - tests/Unit/Http/Handler/PublishedContentHandlerTest.php
      - tests/Unit/Identity/Application/Administration/AccessControlServiceTest.php
      - tests/Unit/Identity/Application/StepUp/TotpStepUpProviderTest.php
      - tests/Unit/Identity/Infrastructure/Administration/DoctrineAdministratorSessionStoreTest.php
      - tests/Unit/Infrastructure/Persistence/Migration/MigrationRunnerTest.php
      - tests/Unit/InterfaceStandard/PresentationPreferenceManagerTest.php
      - tests/Unit/Localization/Application/MessageOverrideServiceTest.php
      - tests/Unit/Navigation/Application/NavigationServiceTest.php
      - tests/Unit/Site/Application/PublicPageLocatorTest.php
      - tests/Unit/Site/Infrastructure/Persistence/DoctrineSiteSettingsTest.php
      - tests/Unit/Studio/Application/Authoring/ContentStudioAuthoringContextAuthorityTest.php
      - tests/Unit/Studio/Application/Host/StudioProducerMutationBoundaryTest.php
      - tests/Unit/Studio/Application/Media/StudioMediaAuditTest.php
      - tests/Unit/Studio/Application/Media/StudioMediaLifecycleTest.php
      - tests/Unit/Studio/Application/Preview/ContentStudioPreviewBindingSourceTest.php
      - tests/Unit/Studio/Application/Projection/ContentStudioResourceSearchProviderTest.php
      - tests/Unit/Studio/Application/Projection/StudioContentProjectionServiceTest.php
      - tests/Unit/Support/TransientBusinessDefinitionFixtureScopeTest.php
    external: []
  dependency_injection:
    mode: direct
    provider: null
    factories: []
    aliases: []
    service_lifetimes:
      - "Kumwe\\Transaction\\Contract\\TransactionManager: request-supplied (bound to one connection by the host)"
      - "Kumwe\\Transaction\\Contract\\TransactionState: request-supplied (bound to one connection by the host)"
    configuration_keys: []
    provider_absence_reason: "Contracts only, no runtime service: the host binds its adapter to the contract FQCNs."
native_cpp: null
php_extension: null
tests:
  moved_or_added:
    - tests/Case/TransactionManagerContractTest.php
    - tests/Case/TransactionStateContractTest.php
    - tests/Case/ImmediateTransactionManagerTest.php
    - tests/Case/ManifestsTest.php
    - tests/Case/ArchitectureTest.php
    - tests/Case/ExamplesTest.php
    - tests/Case/DocumentationTest.php
  remain_in_app_or_consumer:
    - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    - tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php
    - tests/Architecture/TransactionSeamBoundaryTest.php
    - "every App test that composes a service over the port (fixtures_and_examples above), imports updated"
  split_tests:
    - "TransactionSeamBoundaryTest.php: ownership assertion moves here; driver-type and layer assertions stay in App"
  prohibited_duplicates:
    - tests/Support/ImmediateTransactionManager.php
    - "tests/Unit/Application/Automation/PurgeBusinessRecordIdempotencyHandlerTest.php (inline double)"
  corpora: []
documentation:
  charter: CHARTER.md
  readme: README.md
  public_api: docs/public-api.md
  architecture: docs/architecture.md
  integration_or_consumer: docs/integration.md
  examples:
    - examples/typed-consumer.php
    - examples/README.md
  changelog_record: "CHANGELOG.md ## 0.1.0"
release_expectations:
  version_policy: "SemVer, exact pins while pre-1.0; the newest CHANGELOG.md heading (## 0.1.0) is the release record"
  expected_artifact_types:
    - "Composer dist archive of the tag release-on-record creates from the changelog heading, then Packagist"
  required_checks:
    - "composer check (validate, lint, docs, architecture, manifests, autoload smoke, example, cs, analyse, test)"
    - "composer clean-consumer (the last step of composer check: built archive, no-dev install, smoke, example)"
    - "no-dev classmap-authoritative install in the checkout with autoload smoke and example"
    - "composer audit --abandoned=fail"
    - "GitHub Actions: Transaction CI on the pull request head, Release on record on main"
  required_registry_or_installer: Packagist
  required_external_attestation: true
next_task:
  phase_name: "Phase 2 App adoption of kumwe/transaction"
  permitted_only_when:
    - "a human has merged this pull request to main and Release on record has published the recorded version"
    - "a fresh session produced RELEASE-ATTESTATION.yaml with status verified for the published kumwe/transaction"
    - "the App governance bootstrap pull request (NRM-2026-001) is merged on kumwe/app master"
    - "the extracted symbols and their tests were diffed between the baseline commit and current master (D-GOV-7)"
  consumer_repository: kumwe/app
  dependency_or_native_change: "composer require kumwe/transaction:<verified>; exact pin; Composer regenerates the lock"
  namespace_or_api_replacements:
    - 'Kumwe\App\Application\Persistence\TransactionManager -> Kumwe\Transaction\Contract\TransactionManager'
    - 'Kumwe\App\Application\Persistence\TransactionState -> Kumwe\Transaction\Contract\TransactionState'
    - 'Kumwe\App\Tests\Support\ImmediateTransactionManager -> Kumwe\Transaction\Testing\ImmediateTransactionManager'
  files_to_update:
    - composer.json
    - src/Kernel/ContainerFactory.php
    - src/Administrator/Http/Handler/AdministratorAccessControlHandler.php
    - src/Administrator/Http/Handler/AdministratorBusinessSecurityHandler.php
    - src/Application/Authorization/ResourceOwnershipScopeService.php
    - src/Application/Authorization/SiteGroupAdministration.php
    - src/Application/Automation/AutomationManagementService.php
    - src/Application/Presentation/Preference/PresentationPreferenceManager.php
    - src/Audit/Infrastructure/Persistence/DoctrineAuditAnchorWriter.php
    - src/Audit/Infrastructure/Persistence/DoctrineAuditRetentionService.php
    - src/Audit/Infrastructure/Persistence/DoctrineAuditTrailExporter.php
    - src/BusinessDefinition/Application/BusinessDefinitionService.php
    - src/BusinessIntegration/Application/IntegrationEventConsumerDispatcher.php
    - src/BusinessIntegration/Application/IntegrationOperationsService.php
    - src/BusinessIntegration/Application/ProcessWorkDispatcher.php
    - src/BusinessIntegration/Infrastructure/ContributedScheduleSynchronizer.php
    - src/BusinessIntegration/Infrastructure/DoctrineInboxStore.php
    - src/BusinessIntegration/Infrastructure/DoctrineOutboxStore.php
    - src/BusinessIntegration/Infrastructure/DoctrineProcessManagerStore.php
    - src/BusinessRecord/Application/BusinessRecordIdempotencyPurger.php
    - src/BusinessRecord/Application/BusinessRecordService.php
    - src/BusinessRecord/Application/PostingPeriodService.php
    - src/BusinessRecord/Infrastructure/Persistence/DoctrineRecordSecretRotation.php
    - src/BusinessReporting/Application/ExportAttemptPublisher.php
    - src/BusinessReporting/Application/ExportGenerationService.php
    - src/BusinessReporting/Application/ExportService.php
    - src/BusinessReporting/Infrastructure/DoctrineExportArtifactRepository.php
    - src/BusinessReporting/Infrastructure/DoctrineProjectionRuntime.php
    - src/BusinessReporting/Infrastructure/DoctrineProjectionStore.php
    - src/BusinessReporting/Infrastructure/FilesystemExportArtifactRepository.php
    - src/BusinessSchema/Application/BusinessSchemaExecutor.php
    - src/BusinessSchema/Application/BusinessSchemaPlanner.php
    - src/BusinessSchema/Application/BusinessSchemaService.php
    - src/BusinessSecurity/Application/Administration/BusinessSecurityAdministrationService.php
    - src/BusinessSecurity/Application/Approval/ApprovalService.php
    - src/BusinessSurface/Application/BusinessMutationPlanService.php
    - src/BusinessSurface/Application/BusinessOperationStatusService.php
    - src/BusinessSurface/Application/BusinessSurfaceCatalog.php
    - src/BusinessSurface/Application/BusinessSurfaceService.php
    - src/BusinessSurface/Application/CustomBusinessActionExecutor.php
    - src/BusinessSurface/Application/GeneratedBusinessActionStepUp.php
    - src/Content/Application/ContentModelService.php
    - src/Content/Application/ContentService.php
    - src/Delivery/Http/Api/Idempotency/PersistentIdempotencyMiddleware.php
    - src/Delivery/Http/Api/Idempotency/SecretOnceIdempotencyMiddleware.php
    - src/Demo/Infrastructure/DemoAccessProvisioner.php
    - src/Demo/Infrastructure/DemoContentProfileInstaller.php
    - src/Demo/Infrastructure/VdmBusinessDemoInstaller.php
    - src/Extension/Application/Trust/TrustStore.php
    - src/Extension/Infrastructure/DoctrineExtensionManager.php
    - src/Identity/Application/Administration/AccessControlService.php
    - src/Identity/Application/StepUp/TotpStepUpProvider.php
    - src/Identity/Infrastructure/Administration/DoctrineAdministratorIdentityGateway.php
    - src/Identity/Infrastructure/Administration/DoctrineAdministratorSessionStore.php
    - src/Infrastructure/Automation/DoctrineJobQueue.php
    - src/Infrastructure/Automation/DoctrineQueueRuntimeOperations.php
    - src/Infrastructure/Automation/DoctrineScheduler.php
    - src/Infrastructure/Mcp/McpMutationGuard.php
    - src/Infrastructure/Persistence/DoctrineTransactionManager.php
    - src/Infrastructure/Persistence/DoctrineTransactionState.php
    - src/Infrastructure/Persistence/Migration/MigrationRunner.php
    - src/Localization/Application/MessageOverrideService.php
    - src/Media/Application/MediaService.php
    - src/Navigation/Application/NavigationService.php
    - src/Portal/Http/Handler/PortalApprovalHandler.php
    - src/Portal/Infrastructure/Session/DoctrinePortalSessionStore.php
    - src/Presentation/Infrastructure/DoctrineAdministratorThemeRecovery.php
    - src/Site/Infrastructure/Persistence/DoctrineSiteSettings.php
    - src/Studio/Application/Composition/StudioContentCompositionService.php
    - src/Studio/Application/Host/StudioProducerHostFactory.php
    - src/Studio/Application/Host/StudioProducerMutationBoundary.php
    - src/Studio/Application/Media/StudioMediaService.php
    - tests/Architecture/TransactionSeamBoundaryTest.php
    - tests/Integration/Automation/AutomationManagementIntegrationTest.php
    - tests/Integration/BusinessIntegration/PoisonAndDeadLetterIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessNumberSequenceIdentityIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordHistoryPagingIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordInverseRelationshipIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordRelationshipIntegrationTest.php
    - tests/Integration/BusinessRecord/BusinessRecordRuntimeIntegrationTest.php
    - tests/Integration/BusinessSchema/BusinessSchemaExecutionStateGuardIntegrationTest.php
    - tests/Integration/BusinessSchema/BusinessSchemaRecoveryIntegrationTest.php
    - tests/Integration/BusinessSurface/GeneratedBusinessQueryBudgetIntegrationTest.php
    - tests/Integration/Localization/MessageOverrideIntegrationTest.php
    - tests/Integration/Mcp/McpMutationGuardIntegrationTest.php
    - tests/Integration/Mcp/McpTrustLifecycleIntegrationTest.php
    - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    - tests/Integration/Studio/StudioArtifactRecoveryVectorReplayIntegrationTest.php
    - tests/Support/ImmediateTransactionManager.php
    - tests/Support/NeutralBusinessFixture.php
    - tests/Support/TestKernelFactory.php
    - tests/Support/TransientBusinessDefinitionFixtureScope.php
    - tests/Support/prepare-browser-contribution.php
    - tests/Unit/Administrator/Http/Handler/AdministratorAccessControlHandlerTest.php
    - tests/Unit/Administrator/Http/Handler/AdministratorContentEditorRetentionTest.php
    - tests/Unit/Administrator/Http/Handler/AdministratorDashboardHandlerTest.php
    - tests/Unit/Administrator/Http/Handler/AdministratorStudioHostHandlerTest.php
    - tests/Unit/Application/Authorization/AdapterAuthorizationParityTest.php
    - tests/Unit/Application/Authorization/ApplicationAuthorizationTest.php
    - tests/Unit/Application/Authorization/ResourceOwnershipScopeServiceTest.php
    - tests/Unit/Application/Authorization/SiteGroupAdministrationTest.php
    - tests/Unit/Application/Automation/AutomationManagementServiceTest.php
    - tests/Unit/Application/Automation/PurgeBusinessRecordIdempotencyHandlerTest.php
    - tests/Unit/BusinessIntegration/ConsumerDispatcherTest.php
    - tests/Unit/BusinessIntegration/Infrastructure/RuntimeIntegrationEventTransportTest.php
    - tests/Unit/BusinessIntegration/IntegrationOperationsServiceTest.php
    - tests/Unit/BusinessIntegration/ProcessWorkDispatcherTest.php
    - tests/Unit/BusinessRecord/Application/PostingPeriodServiceTest.php
    - tests/Unit/BusinessReporting/ExportGenerationPolicyFenceTest.php
    - tests/Unit/BusinessReporting/ExportServiceTransactionTest.php
    - tests/Unit/BusinessReporting/RecordExportPipelineTest.php
    - tests/Unit/BusinessSecurity/Application/ApprovalServiceTest.php
    - tests/Unit/BusinessSecurity/Application/BusinessSecurityAdministrationServiceTest.php
    - tests/Unit/BusinessSurface/Application/BusinessMutationPlanServiceTest.php
    - tests/Unit/BusinessSurface/Application/BusinessSurfaceCatalogTest.php
    - tests/Unit/BusinessSurface/Application/BusinessSurfaceServiceTest.php
    - tests/Unit/BusinessSurface/Application/Custom/CustomBusinessActionExecutorTest.php
    - tests/Unit/BusinessSurface/Application/GeneratedBusinessActionStepUpTest.php
    - tests/Unit/Content/Application/ContentTranslationServiceTest.php
    - tests/Unit/Content/Application/ContributedContentTranslationTest.php
    - tests/Unit/Content/Presentation/TranslationGroupPresenterTest.php
    - tests/Unit/Delivery/Console/Command/DemoInstallCommandTest.php
    - tests/Unit/Delivery/Console/Command/ManagePostingPeriodsCommandTest.php
    - tests/Unit/Delivery/Http/Api/Automation/AutomationApiHandlerTest.php
    - tests/Unit/Delivery/Http/Api/Business/BusinessOperationStatusApiHandlerTest.php
    - tests/Unit/Delivery/Http/Api/Business/BusinessRecordApiHandlerTest.php
    - tests/Unit/Delivery/Http/Api/Business/PostingPeriodApiHandlerTest.php
    - tests/Unit/Delivery/Http/Api/Idempotency/PersistentIdempotencyAuthorizationTest.php
    - tests/Unit/Delivery/Http/Api/Idempotency/PersistentIdempotencyMiddlewareTest.php
    - tests/Unit/Demo/Infrastructure/DemoExampleExtensionInstallerTest.php
    - tests/Unit/Demo/Infrastructure/VdmBusinessDemoInstallerTest.php
    - tests/Unit/Extension/Application/Trust/TrustStoreTest.php
    - tests/Unit/Extension/Runtime/TrustEnforcingJobHandlerTest.php
    - tests/Unit/Http/Handler/HomePageHandlerTest.php
    - tests/Unit/Http/Handler/PublishedContentHandlerTest.php
    - tests/Unit/Identity/Application/Administration/AccessControlServiceTest.php
    - tests/Unit/Identity/Application/StepUp/TotpStepUpProviderTest.php
    - tests/Unit/Identity/Infrastructure/Administration/DoctrineAdministratorSessionStoreTest.php
    - tests/Unit/Infrastructure/Persistence/Migration/MigrationRunnerTest.php
    - tests/Unit/InterfaceStandard/PresentationPreferenceManagerTest.php
    - tests/Unit/Localization/Application/MessageOverrideServiceTest.php
    - tests/Unit/Navigation/Application/NavigationServiceTest.php
    - tests/Unit/Site/Application/PublicPageLocatorTest.php
    - tests/Unit/Site/Infrastructure/Persistence/DoctrineSiteSettingsTest.php
    - tests/Unit/Studio/Application/Authoring/ContentStudioAuthoringContextAuthorityTest.php
    - tests/Unit/Studio/Application/Host/StudioProducerMutationBoundaryTest.php
    - tests/Unit/Studio/Application/Media/StudioMediaAuditTest.php
    - tests/Unit/Studio/Application/Media/StudioMediaLifecycleTest.php
    - tests/Unit/Studio/Application/Preview/ContentStudioPreviewBindingSourceTest.php
    - tests/Unit/Studio/Application/Projection/ContentStudioResourceSearchProviderTest.php
    - tests/Unit/Studio/Application/Projection/StudioContentProjectionServiceTest.php
    - tests/Unit/Support/TransientBusinessDefinitionFixtureScopeTest.php
    - docs/architecture/layers.json
    - docs/architecture/capability-index.md
    - docs/architecture/governance/core-growth-baseline.json
    - docs/quality/baseline.json
    - CHANGELOG.md
  files_to_remove:
    - src/Application/Persistence/TransactionManager.php
    - src/Application/Persistence/TransactionState.php
    - tests/Support/ImmediateTransactionManager.php
  tests_to_remove:
    - "tests/Unit/Application/Automation/PurgeBusinessRecordIdempotencyHandlerTest.php (inline double)"
  tests_to_retain_or_add:
    - tests/Integration/Persistence/DoctrineTransactionManagerTest.php
    - tests/Integration/Persistence/TransactionBoundaryEngineIntegrationTest.php
    - tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php
    - "tests/Architecture/TransactionSeamBoundaryTest.php with its ownership assertion rewritten to the package FQCNs"
    - "every consumer test in fixtures_and_examples, updated to the canonical imports and the package double"
  di_or_provisioning_changes:
    - "keep both share() bindings in src/Kernel/ContainerFactory.php keyed by the package FQCNs; no alias or fallback"
    - "no ConfigProvider to register: the package ships none"
  capability_index_changes:
    - "composer kumwe:capability-index adds the v2-manifested kumwe/transaction entry with its three capabilities"
    - "docs/architecture/layers.json: add Kumwe\\Transaction to first_party_namespaces and classify Contract as shared"
    - "docs/architecture/layers.json: classify Testing only if the graph tool requires it; src/ must never reference it"
    - "composer kumwe:core-growth-record removes the two retired FQCNs from the baseline"
  changelog_and_evidence_changes:
    - "CHANGELOG.md entry citing NRM-2026-002 and the App pull request"
    - "docs/architecture/non-roadmap/NRM-2026-002.yaml, this migration's non-roadmap record"
    - "docs/architecture/migrations/KUMWE-MIG-2026-001.yaml with handoff_sha256 of the installed handoff"
    - "docs/architecture/migrations/change-sets/KUMWE-CS-2026-001.yaml at state app-pr-ready"
    - "docs/architecture/migrations/trains/<train id>.yaml, a single-PR train (D-GOV-8)"
    - "docs/architecture/migrations/evidence/KUMWE-MIG-2026-001/RELEASE-ATTESTATION.yaml copied unchanged"
  verification_commands:
    - "composer qa"
    - "composer kumwe:capability-index && composer kumwe:capability-index-check"
    - "composer kumwe:core-growth-record && composer kumwe:core-growth-check"
    - "composer baseline:record"
    - "composer test:unit"
    - "the merge workflow's MariaDB, MySQL and PostgreSQL matrix over tests/Integration/Persistence"
    - "grep -rn 'Kumwe.App.Application.Persistence' src tests config bootstrap docs/architecture returns nothing"
concurrency:
  likely_conflict_files:
    - composer.json
    - composer.lock
    - src/Kernel/ContainerFactory.php
    - docs/architecture/layers.json
    - docs/architecture/capability-index.md
    - docs/architecture/governance/core-growth-baseline.json
    - docs/architecture/governance/legacy-packages.json
    - docs/quality/baseline.json
    - CHANGELOG.md
  related_migrations: []
  ownership_conflicts: []
  integration_train: null
  resolution_rule: semantic-preservation
governance:
  roadmap_source_sha256: a202155ef1a65f5ab293d4f8397ebf4ac430db7f1e877c776bbe7851e6fe18d8
  roadmap_refs: []
  non_roadmap_refs:
    - NRM-2026-002
  completion_claim: false
decisions:
  - "D1: ship the App test double as Testing\\ImmediateTransactionManager, test-scoped and never bound (section 5)"
  - "D2: contract-only package with no ConfigProvider, factory or alias; the host binds its adapter to the FQCNs"
  - "D3: every signature and documented semantic is preserved; @since restarts at 0.1.0 in this package"
  - "D4: runtime php ^8.5 alone; local gates ran on PHP 8.4.19 with --ignore-platform-req=php, CI proves 8.5"
  - "D5: the first release is 0.1.0, the newest CHANGELOG.md heading (D-GOV-6); no tag or digest is predicted"
  - "D6: NRM-2026-002 is allocated for the App adoption record, the next free id at the baseline (D-GOV-3)"
  - "D7: the archive gate requires this handoff, so the pre-handoff head fails only that gate, by design"
  - "D8: roadmap_refs stay empty; Sessions 3, 6 and 7 name transaction as enabling, the change set records refs"
  - "D9: the suite is the dependency-free runner (tests/run.php), the pattern kumwe/conversion uses"
blockers: []
---

## 1. Migration/implementation summary

The storage-neutral transaction port moved from Kumwe App to this package under `Kumwe\Transaction\`:
`Contract\TransactionManager` (run an operation atomically; register a side effect for after the outermost
commit; register a compensation for a discarded scope) and `Contract\TransactionState` (whether any
transaction scope, however deeply nested and whoever opened it, is open on the connection). Both interfaces
keep the App's method names, the single `callable` parameter, the `mixed`/`void`/`bool` returns, the
`@template T` return contract and the documented semantics, sentence by sentence; the only changes are the
namespace, package-neutral wording where the App's blocks named `DoctrineTransactionManager`, and `@since`
restarting at `0.1.0`.

The App's test support `tests/Support/ImmediateTransactionManager.php` moved as
`Testing\ImmediateTransactionManager`, behaviour unchanged (the class is now `final readonly`, which changes
nothing observable for a class without state). It is explicitly test-scoped: documented as test support on
the class, declared under its own `transaction.testing` capability whose description says it is for
consumer test suites only and never a production implementation, absent from the service map, and refused
by the architecture gate as a dependency of the contract layer.

Deliberately not moved: `DoctrineTransactionManager` and `DoctrineTransactionState` (they own a DBAL
connection and the frame-stack nesting policy), the two `share()` bindings in `src/Kernel/ContainerFactory.php`
(composition is host authority), retry, deadlock, timeout, logging, audit and outbox coordination (policy
around the port), the real-database evidence on MariaDB, MySQL and PostgreSQL, and the App's inline
scope-recording test doubles (test-local probes). No Kumwe dependency was selected: the allowed ceiling is
None and nothing in the closure needs one. Dependency injection is contract-only: no `ConfigProvider`, no
factory, no alias, with the reason recorded in the service map. Kumwe App is unchanged by this phase.

## 2. Public API and responsibility

Responsibility: the storage-neutral transaction port — atomic scopes, settlement hooks and an
open-transaction view; the host supplies the adapter. Non-responsibilities are listed in the front matter
and in [`CHARTER.md`](CHARTER.md).

| Symbol | Capability |
| --- | --- |
| `Kumwe\Transaction\Contract\TransactionManager` (interface, extension point) | `transaction.boundary` |
| `Kumwe\Transaction\Contract\TransactionState` (interface, extension point) | `transaction.state` |
| `Kumwe\Transaction\Testing\ImmediateTransactionManager` (final class, test support) | `transaction.testing` |

Every public member is documented in [`docs/public-api.md`](docs/public-api.md) with parameters, return,
invariants, exceptions, side effects, state, nullability, precision, transaction expectations, concurrency
and an example; the reflected surface is pinned in
[`resources/public-api/v1.json`](resources/public-api/v1.json), the capabilities in
[`resources/capabilities/v1.json`](resources/capabilities/v1.json) and the provider decision in
[`resources/service-map/v1.json`](resources/service-map/v1.json). The manifest digests in the front matter
are the digests of those files as committed with this handoff; `composer manifests` regenerates the public
API manifest from reflection and refuses drift. [`README.md`](README.md) carries the twelve sections the
package standard requires; [`docs/architecture.md`](docs/architecture.md) the layer rules and the lane;
[`docs/integration.md`](docs/integration.md) the adapter obligations, the binding and the test double;
[`docs/releasing.md`](docs/releasing.md) and [`docs/security.md`](docs/security.md) the release,
compatibility and security policies.

## 3. Capability reuse/semantic input review

Capability index inspected: `docs/architecture/capability-index.md` at the baseline commit, index digest
`87ded886f35f74878ca9eb8db4c36e23d681c4a49891f76dfc3210f385a7ce39` (three packages, all
legacy-unmanifested). Installed releases from `composer show --locked "kumwe/*"`: `kumwe/conversion 0.1.2`,
`kumwe/extension-sdk 0.2.4`, `kumwe/producer 0.2.0`. Their source trees under `vendor/kumwe/*/src` were
searched by responsibility and behaviour — `transaction`, `transactional`, `afterCommit`, `afterRollback`,
`isActive`, `commit`, `rollback`, `atomic`, `unit of work` — as well as by symbol name. No installed package
owns a transaction port. The only hits are `kumwe/producer`'s `Wire\Port\MutationBoundaryInterface` and
`Wire\MutationOutcome`, a host-implemented atomic mutation boundary for Studio wire operations (the App's
`StudioProducerMutationBoundary` implements it on top of this port, not instead of it), and prose in
`kumwe/extension-sdk`'s domain-event contracts. The catalog (Kumwe-v2-05, row 2) names this repository as
the sole owner with an allowed ceiling of None, and `kumwe/approval` as a later dependent. No semantic
input, corpus or upstream handoff exists for this package; none is required. `kumwe/conversion` at v0.1.2
and `kumwe/producer` at v0.2.0 were consulted as the hardened release and clean-consumer patterns to
emulate, not as dependencies.

## 4. Consumer inventory

Recomputed at the baseline commit with `grep -rl` over `src/`, `tests/`, `config/`, `bootstrap/` and
`examples/`, then narrowed to files importing either port: 70 production files import the
port (listed under `consumers.app_code`, including the two Doctrine adapters), `src/Kernel/ContainerFactory.php`
binds both contracts and resolves `TransactionManager` into more than sixty services, 81 test
files import the port or the App's support double (listed under `consumers.fixtures_and_examples`), and two
files reference the old names outside a `use` statement: `tests/Architecture/TransactionSeamBoundaryTest.php`
(asserts the `Kumwe\App\Application\` prefix and the `src/Application/Persistence` directory of the port)
and `docs/architecture/governance/core-growth-baseline.json` (baseline entries for both FQCNs). `config/`,
`bootstrap/`, `examples/` and `resources/` reference neither. Three documents mention the port by short name
only (`docs/architecture/map.md`, `docs/architecture/core-growth/README.md`,
`docs/qualification/gap-matrix.md`); their wording is reviewed at adoption, and the historical entry in the
App's `CHANGELOG.md` is left as history. `TransactionState` has three production consumers
(`ContainerFactory`, `StudioProducerHostFactory`, `StudioProducerMutationBoundary`) and three test
consumers. Seventeen App test files depend on the App support double that this package now owns.

## 5. Test ownership

Moved or added to this package: the seven cases under `tests/Case/`, which pin both interface shapes, the
generic return contract and the documented semantics; prove the double's behaviour including its
non-promises (a commit hook registered before a failure has already run; rollback hooks are always
discarded); hold the three manifests to the source tree, the changelog and the API document; prove the
architecture boundary and the release archive's file set; replay the shipped example; and check every
document's relative links and the README's required sections.

Retained in App: `tests/Integration/Persistence/DoctrineTransactionManagerTest.php` and
`TransactionBoundaryEngineIntegrationTest.php` (durability, residue, driver-failure classification,
retryable contention, nested-scope fate, audit and outbox atomicity on real engines),
`tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php` (the adapter against a stubbed
connection), every consumer test, and the App's inline scope-recording doubles (`RecordingProcessTransactions`,
`ExportTransactionProbe`, `GenerationFenceTransactions`, `RecordingTransactionManager`,
`CommitBarrierTransactionManager` and the anonymous classes), which observe App services and are not
package behaviour.

Split: `tests/Architecture/TransactionSeamBoundaryTest.php` — its assertion that the port lives under
`Kumwe\App\Application\` in `src/Application/Persistence` is now this package's
`TransactionManagerContractTest::testThePortIsAnInterfaceWithExactlyThreeOperations`; its assertions that
no driver type crosses the port's signatures and that no application class or file names a driver stay in
App, rewritten to reference the package FQCN.

Prohibited duplicates after adoption: `tests/Support/ImmediateTransactionManager.php` and the inline
`ImmediateTransactionManager` class in
`tests/Unit/Application/Automation/PurgeBusinessRecordIdempotencyHandlerTest.php`, both byte-for-byte
equivalents of the package double. The decision to ship the double (D1): seventeen App test files import the
support class and one test declares an identical inline copy, so a package-owned double replaces two App
copies with one owner; it is test-scoped in documentation, capability and architecture gate, it cannot be
resolved from a container because nothing registers it, and it leaves App no production fallback. The
alternative — keeping the double in App — would leave a second owner of the same three-method behaviour
next to the package's contract tests.

## 6. Next-task execution notes

1. Verify the published release and this handoff independently (Kumwe-v2-11) and confirm
   `RELEASE-ATTESTATION.yaml` reports `status: verified`; stop otherwise.
2. Run the drift check of section 7; route any portable change into a successor release first.
3. `composer require kumwe/transaction:<verified version>` in `kumwe/app` with the exact pin; let Composer
   regenerate `composer.lock`; never hand-edit it.
4. Replace the three names everywhere (`namespace_or_api_replacements`): the `use` statements in the
   70 production files and 81 test files, `src/Kernel/ContainerFactory.php`, and
   the seam test's string assertions. Delete `src/Application/Persistence/TransactionManager.php`,
   `src/Application/Persistence/TransactionState.php` and `tests/Support/ImmediateTransactionManager.php`;
   delete the inline `ImmediateTransactionManager` class in `PurgeBusinessRecordIdempotencyHandlerTest.php`
   and import the package double instead.
5. Keep both `share()` bindings in `ContainerFactory`, now keyed by
   `Kumwe\Transaction\Contract\TransactionManager` and `Kumwe\Transaction\Contract\TransactionState`;
   register no alias for the retired names and no fallback.
6. In `docs/architecture/layers.json` add `Kumwe\Transaction` to `first_party_namespaces` and classify
   `Kumwe\Transaction\Contract` as `shared`; run `php tools/verify-dependency-graph.php`. Add a rule for
   `Kumwe\Transaction\Testing` only if the tool demands one for a namespace referenced from tests; `src/`
   must never reference it.
7. `composer kumwe:capability-index` (the entry is `v2-manifested`; its `handoff` digest is the sha256 of
   `vendor/kumwe/transaction/MIGRATION-HANDOFF.md`), then `composer kumwe:core-growth-record` to drop the two
   retired FQCNs, then `composer baseline:record` for the moved test.
8. Write `docs/architecture/migrations/KUMWE-MIG-2026-001.yaml`,
   `docs/architecture/migrations/change-sets/KUMWE-CS-2026-001.yaml` (`app-pr-ready`),
   `docs/architecture/non-roadmap/NRM-2026-002.yaml`, the single-PR train record, and copy the attestation to
   `docs/architecture/migrations/evidence/KUMWE-MIG-2026-001/`; add the `CHANGELOG.md` entry citing
   `NRM-2026-002` and the App PR number. Claim no roadmap objective.
9. Run every command in `verification_commands`, including the three-engine integration matrix, and open
   the App PR against `master`; stop without merging.

## 7. Drift check

At Phase 2 start, in `kumwe/app`, compare the extracted symbols and their tests between the baseline and the
current target:

```text
git diff 6f9e42cb59a84ba3ca523a70475cf4d7263c68e7..origin/master -- \
  src/Application/Persistence tests/Support/ImmediateTransactionManager.php \
  tests/Architecture/TransactionSeamBoundaryTest.php tests/Integration/Persistence \
  tests/Unit/Infrastructure/Persistence/DoctrineTransactionStateTest.php
```

A change to either interface's signature, generic contract or documented semantics, or to the support
double's behaviour, is portable: port it to this package, release a successor version, verify it, and adopt
that version instead. A change to the Doctrine adapters, the bindings or the database tests is App-specific
and stays in App. Unrelated App changes are ignored (D-GOV-7). Never delete a newer App implementation
because an older snapshot was extracted.

## 8. Validation recipe and observed local results

Recipe, from a clean clone of the pull-request branch:

```text
composer install
composer check
composer install --no-dev --classmap-authoritative && composer autoload:smoke && composer examples
```

Authoring environment: PHP 8.4.19 (the package declares `^8.5`; Composer ran with
`--ignore-platform-req=php` and the clean-consumer gate with
`KUMWE_CLEAN_CONSUMER_COMPOSER_ARGS=--ignore-platform-req=php`), Composer 2.8.12 with the development
packages installed from source because dist downloads are blocked there, PHPStan 2.2.12, PHP_CodeSniffer
4.0.4. Continuous integration runs the same lane on real PHP 8.5.

Observed on the tree that contains this handoff, before it was committed (the final tested head identity is
external to this file): `composer validate --strict` valid; lint 20 PHP files and every tracked file within
120 columns; member documentation complete (14 members, 4 files); architecture verified (3 source files);
manifests verified (3 public symbols, 3 capabilities, no provider with reason, release 0.1.0 documented);
autoload smoke 3 symbols; the example's output as documented; `phpcs` clean; PHPStan level max with strict
and deprecation rules clean; the suite 29 tests, 209 assertions; the clean-consumer gate green (archive file
set verified, no-dev classmap-authoritative install, smoke and example inside the archive). Before this
handoff existed the same lane was green except the clean-consumer gate, which refused the archive for the
missing `MIGRATION-HANDOFF.md` by design (D7). The three manifests and this front matter validate against
the Kumwe App governance schemas, and the App's `PackageManifests` reader accepts the package as
`v2-manifested`. A gitleaks history scan could not run locally (no Docker); the tree carries no
configuration or credential material, and `composer audit --abandoned=fail` runs in CI.
