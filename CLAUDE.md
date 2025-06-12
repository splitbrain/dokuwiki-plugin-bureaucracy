# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Testing

Run tests for the bureaucracy plugin:
```bash
../../../bin/plugin.php dev test
```

## Architecture Overview

The bureaucracy plugin is a DokuWiki form builder that allows creating forms in wiki pages. Forms can submit data via email or create new pages from templates.

### Core Components

- **syntax.php**: Main syntax plugin that parses `<form>...</form>` blocks and renders HTML forms
- **action.php**: Action plugin handling AJAX requests for user field autocomplete
- **helper/**: Contains field types and action handlers
  - **field.php**: Base class for all form fields
  - **action.php**: Base class for all form actions
  - **field*.php**: Specific field implementations (textbox, email, date, file, etc.)
  - **action*.php**: Action implementations (email, template, script)

### Form Processing Flow

1. **Parsing**: syntax.php parses form definition syntax into field and action objects
2. **Rendering**: Each field renders its HTML using templates
3. **Validation**: On submit, each field validates its input
4. **Actions**: If validation passes, configured actions execute (email, page creation, etc.)

### Field System

Fields inherit from `helper_plugin_bureaucracy_field` and implement:
- `renderfield()`: Generate form HTML
- `handle_post()`: Validate and process submitted data
- Standard arguments: label, default value, validation rules, optional flag

### Action System

Actions inherit from `helper_plugin_bureaucracy_action` and implement:
- `run()`: Process form data after validation
- Common actions: template (create pages), mail (send emails), script (run custom code)

### Replacement System

The plugin supports placeholder replacement in templates and values:
- Page-based: `@FORMPAGE_ID@`, `@FORMPAGE_NS@`
- User-based: `@USER@`, `@NAME@`, `@MAIL@`
- Date/time: `@DATE@`, `@YEAR@`, `@MONTH@`
- Functions: `@curNS(...)@`, `@getNS(...)@`

## Current Issues and Technical Debt

### 1. Deprecated Form Mechanisms

**Issue**: The bureaucracy plugin uses DokuWiki's deprecated `Doku_Form` class and related form functions instead of the modern `dokuwiki\Form` API.

**Current Implementation**:
- Uses `new Doku_Form()` in syntax.php:378
- Uses deprecated form functions like `form_makeTextField()`, `form_makeListboxField()` in field classes
- Each field class calls `$form->addElement()` with pseudo-tag arrays
- Uses `$form->_infieldset` property to manage fieldset state

**Impact**:
- The `Doku_Form` class is marked as deprecated since 2019-07-14
- Relies on legacy form rendering functions that may be removed in future DokuWiki versions
- Creates technical debt and compatibility issues with newer DokuWiki installations

**Modern Alternative**:
- DokuWiki provides `dokuwiki\Form\Form` class with proper OOP design
- Modern form elements like `InputElement`, `ButtonElement`, `SelectElement`
- Better separation of concerns and cleaner API

**Files Affected**:
- syntax.php:375-389 (`_htmlform` method)
- helper/field.php:150-160 (`renderfield` method)
- All field helper classes (fieldtextbox.php, fieldselect.php, etc.)
- All field classes use deprecated form functions in their `initialize()` methods

### 2. Poor Separation of Concerns and Data Flow

**Issue**: The plugin has unclear separation between field definitions, rendering, value handling, and actions, making data flow difficult to trace and maintain.

**Current Problems**:

**Mixed Responsibilities**:
- `syntax_plugin_bureaucracy` handles parsing, rendering, validation, and action execution
- Field classes mix definition parsing, template storage, rendering, and validation logic
- Actions directly access field internals and manage their own replacement patterns

**Unclear Data Flow**:
1. **Parse Phase** (syntax.php:77-138): Form definition text → field objects + action configs
2. **Render Phase** (syntax.php:203-231): Field objects → HTML form (with value replacement)
3. **Validation Phase** (syntax.php:308-332): POST data → field validation + value storage
4. **Action Phase** (syntax.php:336-366): Field objects → action execution → results

**Specific Issues**:

**Value Management Confusion**:
- Default values set during parsing via `=value` syntax (field.php:95-96)
- Runtime replacement happens in render() (syntax.php:211-214)
- User input validation in handle_post() overwrites values (field.php:201-202)
- Actions read final values via getParam('value') (field.php:341-356)

**Template/Rendering Mixing**:
- Fields store HTML templates during initialization (e.g., fieldtextarea.php:23-27)
- Template parsing mixed with parameter handling (_parse_tpl method)
- Rendering logic scattered across base field class and specific implementations

**Action Dependencies**:
- Actions manually call prepareFieldReplacements() to extract field data
- Each action reimplements similar field data extraction patterns
- No clear interface for field → action data transfer

**Validation State Management**:
- Error state stored in field objects (field.php:25, 287-292)
- Validation interleaved with value setting (setVal method)
- No clear separation between field state and form state

**Files Showing Poor Separation**:
- syntax.php: Single class handling parsing, rendering, validation, and orchestration
- helper/field.php: Mixed field definition, rendering, validation, and data extraction
- helper/action*.php: Actions directly manipulating field replacement patterns
- All field classes: Template storage mixed with behavior logic

### 3. Poor Plugin Integration and Registration Mechanisms

**Issue**: The mechanisms for other plugins to integrate with bureaucracy are poorly defined and rely on fragile naming conventions rather than proper registration APIs.

**Current Integration Problems**:

**Naming Convention Dependencies**:
- Field types: Must follow `bureaucracy_field{type}` or `{plugin}_{component}` naming (syntax.php:118-123)
- Actions: Must follow `bureaucracy_action{type}` or `{plugin}_{component}` naming (syntax.php:144-149)
- File system dependencies: Helpers must exist at `DOKU_PLUGIN/{plugin}/helper/{component}.php` (syntax.php:155)

**Fragile Discovery Mechanism**:
- Uses `loadHelper()` with hardcoded naming patterns (syntax.php:126, 338)
- Falls back to alternative naming `{type}_{type}` for plugins (syntax.php:152)
- File existence checks with `@file_exists()` suppress errors but don't provide feedback

**Limited Registration Events**:
- `PLUGIN_BUREAUCRACY_FIELD_UNKNOWN`: Only fires AFTER field lookup fails (syntax.php:131-136)
- `PLUGIN_BUREAUCRACY_ACTION_UNKNOWN`: Only fires AFTER action lookup fails (syntax.php:165-170)
- Events are fallbacks, not proper registration mechanisms

**Integration Example - Struct Plugin**:
The struct plugin demonstrates both the limitations and workarounds:
- Creates `helper_plugin_struct_field` following naming convention
- Uses `PLUGIN_BUREAUCRACY_FIELD_UNKNOWN` event to handle `struct_schema` type (struct/action/bureaucracy.php:42, 53-79)
- Manually creates field objects and adds them to the fields array
- Must hook into various bureaucracy events: `PLUGIN_BUREAUCRACY_PAGENAME`, `PLUGIN_BUREAUCRACY_EMAIL_SEND`, `PLUGIN_BUREAUCRACY_TEMPLATE_SAVE`

**Problems with Current Approach**:

**No Proper Registration API**:
- No central registry for field types or actions
- No way to register types without creating helper files
- No validation of extension implementations

**Tight Coupling**:
- Extensions must extend `helper_plugin_bureaucracy_field` or `helper_plugin_bureaucracy_action`
- Must follow internal APIs that aren't formally defined
- No interface contracts or documentation for extension developers

**Error Handling**:
- Failed lookups only show generic "unknown type" messages
- No differentiation between missing plugins and incorrect implementations
- Silent failures with `@file_exists()` suppress important error information

**Discoverability Issues**:
- No way to list available field types or actions
- No plugin metadata about bureaucracy extensions
- Extensions scattered across different plugins with no central management

**Files Affected**:
- syntax.php:118-136 (field type discovery)
- syntax.php:140-171 (action type discovery)
- No formal registration system exists
- Extensions must follow undocumented naming patterns and file structure

### 4. Limited and Fragile Field Dependencies

**Issue**: Field dependencies are extremely limited, only working with fieldsets, and the implementation is fragile with duplicated logic between JavaScript and backend validation.

**Current Dependency Limitations**:

**Only Fieldset Dependencies**:
- Dependencies only work for fieldsets, not individual fields (fieldfieldset.php:7-10)
- Cannot create complex dependency chains or multiple conditions
- No support for conditional field visibility, only fieldset visibility

**Simple Dependency Logic Only**:
- Only supports "field equals value" or "field is set" conditions (fieldfieldset.php:80-84)
- No support for complex conditions (AND, OR, NOT, ranges, patterns)
- No support for dependencies on multiple fields
- No computed or derived dependencies

**Fragile JavaScript Implementation**:

**DOM Selector Fragility**:
- Uses fragile CSS selectors: `jQuery("label").has(":first-child:contains('" + fname + "')")` (fieldsets.js:65-66)
- Depends on specific HTML structure and label text matching
- Selector breaks if field HTML structure changes or labels contain special characters

**Complex Logic in JavaScript**:
```javascript
var showOrHide =
    input.parentNode.parentNode.style.display !== 'none' &&                     // input/checkbox is displayed AND
    ((input.checked === dp.tval) ||                                             //  ( checkbox is checked
     (input.type !== 'checkbox' && (dp.tval === true && input.value !== '')) || //  OR no checkbox, but input is set
     input.value === dp.tval);                                                  //  OR input === dp.tval )
```
- Hardcoded logic for different input types (fieldsets.js:24-28)
- Fragile DOM traversal: `input.parentNode.parentNode.style.display`
- Mixes checkbox, text input, and select logic in complex conditionals

**Duplicated Backend Validation**:
- Backend reimpements dependency logic in `fieldfieldset.php:68-99`
- Different algorithm than JavaScript (linear search vs. DOM selectors)
- Risk of JavaScript and backend getting out of sync
- No shared dependency validation logic

**Dependency State Management Issues**:

**Hidden Field State Confusion**:
- Uses `$field->hidden` property to track dependency state (field.php:24)
- Backend sets hidden fields after dependency validation (fieldfieldset.php:89-96)
- JavaScript manipulates `required` attributes but doesn't sync with backend state
- No clear ownership of field visibility state

**Cascading Dependency Problems**:
- JavaScript triggers `change()` events recursively (fieldsets.js:45)
- Can create infinite loops with circular dependencies
- No cycle detection or dependency graph validation

**Event Handling Fragility**:
- Binds to both `change` and `keyup` events (fieldsets.js:75)
- No debouncing or throttling for rapid changes
- Event handlers can fire multiple times for single user action

**Specific Technical Issues**:

**HTML Structure Dependencies**:
- Requires specific fieldset HTML with `<p class="bureaucracy_depends">` elements
- Dependent on specific span classes: `bureaucracy_depends_fname`, `bureaucracy_depends_fvalue`
- JavaScript parsing HTML content for configuration instead of using data attributes

**Limited Value Matching**:
- String comparison only: `input.value === dp.tval` (fieldsets.js:28)
- No type coercion or flexible matching
- Boolean dependencies hardcoded as `dp.tval === true` (fieldsets.js:27)

**No Dependency API**:
- No programmatic way to create or modify dependencies
- Dependencies only configurable through form syntax
- No way for other plugins to hook into dependency system

**Files Affected**:
- script/fieldsets.js:1-84 (fragile JavaScript implementation)
- helper/fieldfieldset.php:68-99 (backend dependency validation)
- syntax.php:321-323 (hidden field handling)
- helper/field.php:24 (hidden state property)

### 5. Outdated Coding Style and Standards

**Issue**: The codebase uses outdated coding practices and inconsistent style that doesn't meet modern PHP standards.

**PHP Language Features**:

**Outdated Array Syntax**:
- Uses old `array()` syntax instead of modern `[]` (syntax.php:24-27, 80, 85)
- Inconsistent usage: some files mix both styles (actionmail.php:35 vs actiontemplate.php:42)
- Should use short array syntax consistently: `$patterns = [];`

**Inconsistent Property Visibility**:
- Mixes `var` (deprecated) with proper visibility modifiers (syntax.php:24-27)
- Uses `var $patterns = array();` instead of `private $patterns = [];`
- Missing visibility declarations on some properties and methods

**Old Control Structure Spacing**:
- Missing spaces in control structures: `if(!$line)`, `foreach($args as $arg)` (syntax.php:88, field.php:94)
- Should follow PSR-12: `if (!$line)`, `foreach ($args as $arg)`
- Inconsistent spacing around operators and parentheses

**Function Naming and Style**:
- Uses function keyword without visibility: `function initialize($args)` (fieldtextbox.php:18)
- Should be `public function initialize($args)`
- Mixes camelCase with snake_case: `_parse_line()`, `_htmlform()` vs `getParam()`

**Error Handling and Practices**:

**Error Suppression Overuse**:
- Uses `@` operator inappropriately: `@preg_match('/' . $d . '/i', $value)` (field.php:424)
- Suppresses file_exists errors: `@file_exists(DOKU_PLUGIN . $plugin . '/helper/' . $component . '.php')` (syntax.php:155)
- Should use proper error handling instead of suppression

**Global Variable Usage**:
- Excessive use of global variables: `global $ID; global $conf;` (actionmail.php:24-25)
- Should use dependency injection or service locators
- Makes testing and maintenance difficult

**Inconsistent Documentation**:

**PHPDoc Inconsistencies**:
- Missing or incomplete type hints in docblocks
- Inconsistent parameter documentation format
- Missing `@throws` declarations where exceptions are thrown

**Code Organization Issues**:

**Long Methods and Classes**:
- `syntax_plugin_bureaucracy::handle()` method is 120+ lines with multiple responsibilities
- `helper_plugin_bureaucracy_field::standardArgs()` has complex nested logic
- Methods should be broken down into smaller, focused functions

**Magic Numbers and String Constants**:
- Hardcoded strings throughout: `'bureaucracy__plugin'`, `'bureaucracy_depends'`
- Magic characters for parsing: `'='`, `'!'`, `'^'`, `'@'`, `'@@'` (field.php:95-107)
- Should use defined constants or enums

**Inconsistent Indentation and Formatting**:

**Mixed Indentation Styles**:
- Inconsistent tab/space usage in some files
- Inconsistent array formatting and alignment
- HTML strings mixed with PHP code without proper formatting

**Inconsistent Brace Placement**:
- Mixed opening brace styles (same line vs. next line)
- Inconsistent closing brace alignment

**Modern PHP Standards Violations**:

**No Type Declarations**:
- Missing parameter and return type hints
- No scalar type declarations (available since PHP 7.0)
- No nullable type hints where appropriate

**No Strict Types**:
- Missing `declare(strict_types=1);` declarations
- Relies on PHP's loose type juggling instead of strict typing

**No Use of Modern Features**:
- No use of null coalescing operator: `$sep = $argv[2] ?? $conf['sepchar'];` (only used in some newer files)
- Could benefit from match expressions, arrow functions, constructor property promotion

**Specific Examples of Style Issues**:

```php
// Bad: Old style, poor spacing, var keyword
var $patterns = array();
if(!defined('DOKU_INC')) die();
foreach($rawactions as $action) {

// Good: Modern style
private array $patterns = [];
if (!defined('DOKU_INC')) {
    die();
}
foreach ($rawactions as $action) {
```

**Files Needing Style Updates**:
- syntax.php: Mixed array syntax, poor spacing, long methods
- helper/field.php: Error suppression, complex methods, poor documentation
- All helper field classes: Missing visibility modifiers, inconsistent style
- JavaScript files: No modern ES6+ features, poor error handling

### 6. Security and Validation Issues

**Issue**: The plugin has several security vulnerabilities and inadequate input validation that could lead to security risks.

**Input Validation Problems**:

**Insufficient Email Validation**:
- Email validation relies on basic `mail_isvalid()` function (fieldemail.php:26)
- No protection against email header injection attacks
- Allows placeholder values like `@MAIL@` without proper validation context

**File Upload Security Risks**:
- Basic filename validation using simple regex: `/^[a-z.\-_:]+$/` (fieldfile.php:23)
- No MIME type validation or file content inspection
- Upload error handling could leak system information (fieldfile.php:65-73)
- File paths constructed using simple concatenation without proper sanitization

**Script Action Security Vulnerability**:
- `actionscript.php` uses dynamic `require` statements (line 30)
- File path validation limited to basic pattern matching (line 18)
- Could potentially be exploited for local file inclusion if script directory is compromised
- Uses dynamic class instantiation without sufficient validation (lines 32-43)

**Cross-Site Scripting (XSS) Risks**:

**Insufficient Output Escaping**:
- Form field values may not be properly escaped in all contexts
- Template replacement system doesn't consistently escape user input
- HTML templates stored as strings with placeholder replacement could inject malicious content

**Data Persistence Issues**:
- Field values stored in PHP sessions/objects without proper sanitization
- Template content processed without adequate filtering

**CSRF Protection Issues**:

**Limited CSRF Protection**:
- Only uses `checkSecurityToken()` for form submission (syntax.php:220)
- No additional nonce validation for file uploads or AJAX requests
- User autocomplete AJAX endpoint might be vulnerable to CSRF (action.php:31-59)

**Access Control Problems**:

**Inadequate Permission Checking**:
- File upload namespace resolution doesn't validate write permissions early enough
- Template access checking has complex logic that might bypass restrictions (actiontemplate.php:121-124)
- `runas` configuration allows privilege escalation without proper audit trail

**Information Disclosure**:

**Error Information Leakage**:
- File system errors exposed to users through exception messages
- Upload errors include system-level information (fieldfile.php:66, 72)
- Missing template files expose server file structure

**Cache Disabling Issues**:
- Forces cache disable for all pages containing forms: `$R->info['cache'] = false` (syntax.php:205)
- Could impact site performance and DDoS resilience
- No selective caching based on form content sensitivity

### 7. Performance and Scalability Issues

**Issue**: The plugin has performance bottlenecks and doesn't scale well with larger forms or high traffic.

**Inefficient Processing**:

**Linear Search Algorithms**:
- Dependency validation uses linear search through all fields (fieldfieldset.php:75-85)
- Email field dependency checking iterates all fields for each fieldset (actionmail.php:117-121)
- No indexing or optimization for large forms

**Repeated Template Processing**:
- Template content parsed and replaced multiple times for each action
- No caching of processed template content
- File system access repeated for namespace template resolution (actiontemplate.php:138-155)

**Memory Usage Issues**:

**Object Proliferation**:
- Creates field objects for every form on every page load
- No object pooling or reuse mechanisms
- Large forms could consume significant memory

**Session/Cache Problems**:
- Disables page caching completely instead of selective invalidation
- Could cause memory bloat on high-traffic sites

**JavaScript Performance Issues**:

**DOM Manipulation Inefficiency**:
- jQuery selectors recalculate on every dependency check (fieldsets.js:65-66)
- No event delegation or optimization for large forms
- Recursive change events could create performance bottlenecks (fieldsets.js:45)

**Files Affected**:
- helper/fieldemail.php:26 (weak email validation)
- helper/fieldfile.php:23,65-73 (file upload security)
- helper/actionscript.php:18,30,32-43 (script inclusion vulnerability)
- syntax.php:205,220 (cache and CSRF issues)
- helper/actiontemplate.php:121-124,138-155 (access control and performance)
- script/fieldsets.js:45,65-66 (JavaScript performance)
- action.php:31-59 (AJAX endpoint security)

## Target Architecture

This section describes the complete architecture of the bureaucracy plugin after refactoring is completed. It shows how the new plugin will work, which classes will exist, and how they interact.

### Core Components Overview

**Data Flow**: Form Definition Text → FormParser → FormData → Form Rendering & Processing → Actions

**Separation of Concerns**:
- **Parsing**: FormParser converts form definition syntax into structured data
- **Data Storage**: FieldData and FormData objects hold all state (immutable value objects)
- **Behavior**: Field and Action classes contain behavior but no state
- **Registration**: Registry classes manage available field and action types
- **Orchestration**: FormProcessor coordinates the entire form lifecycle

### Core Classes and Responsibilities

#### Data Objects (State, No Behavior)

**FieldData** (Immutable Value Object)
- **Purpose**: Holds all configuration and state for a single field
- **Contains**: field type, label, validation rules, current value, error message, required/optional status, CSS classes, dependency conditions
- **Methods**: Getters, `withValue()`, `withError()`, `withHidden()` for creating modified copies
- **Used by**: Parser to store parsed configuration, Field classes to read configuration, Form rendering to get current state, DependencyManager to evaluate conditions

**FormData** (Immutable Value Object)
- **Purpose**: Holds complete form state and configuration
- **Contains**: Collection of FieldData objects, form metadata (thanks message, labels), action configurations, form ID
- **Methods**: Getters, `withFieldData()`, `withValidationState()` for creating modified copies
- **Used by**: All components as the central data container

#### Behavior Classes (Behavior, No State)

**Field Classes** (Implementing FieldInterface)
- **Purpose**: Parse field configuration and create form elements
- **Responsibilities**:
  - Parse field-specific arguments from form definition (e.g., select options, validation patterns)
  - Validate user input according to field type rules
  - Create modern Form elements for rendering
- **Do NOT**: Store any state, manage form-wide concerns, handle dependencies
- **Examples**: `TextboxField`, `SelectField`, `EmailField`, `FileField`
- **Key Methods**:
  - `parseConfiguration(array $args): array` - Convert raw arguments to structured config
  - `validateInput($value): bool` - Validate user input
  - `buildFormElement(FieldData $data): \dokuwiki\Form\Element` - Create form element

**Action Classes** (Implementing ActionInterface)
- **Purpose**: Process form data after successful validation
- **Responsibilities**:
  - Parse action-specific configuration
  - Execute action logic (send email, create page, run script)
  - Return success/thanks message
- **Examples**: `MailAction`, `TemplateAction`, `ScriptAction`
- **Key Methods**:
  - `parseConfiguration(array $args): array` - Parse action arguments
  - `execute(FormData $formData): string` - Perform action and return thanks message

#### Orchestration Classes

**FormParser**
- **Purpose**: Convert form definition text into structured FormData
- **Responsibilities**:
  - Parse `<form>...</form>` syntax
  - Extract field definitions, action definitions, thanks messages, labels
  - Parse dependency syntax for individual fields (new unified syntax)
  - Use FieldFactory and ActionFactory to parse field/action-specific configuration
  - Create immutable FormData object with dependency definitions
- **Does NOT**: Validate field values, handle form submission, manage state, evaluate dependencies

**FormProcessor**
- **Purpose**: Coordinate complete form lifecycle (render, validate, execute actions)
- **Responsibilities**:
  - Render forms using FormData and Field classes
  - Process form submissions by coordinating validation and actions
  - Manage form state transitions
  - Replace the mixed responsibilities currently in syntax_plugin_bureaucracy

**FormRenderer**
- **Purpose**: Generate HTML forms from FormData
- **Responsibilities**:
  - Create dokuwiki\Form\Form objects
  - Iterate through FieldData and call appropriate Field.buildFormElement()
  - Handle fieldsets, dependencies, and form structure
  - Apply CSS classes and form attributes

#### Registration System

**FieldRegistry**
- **Purpose**: Manage available field types
- **Responsibilities**:
  - Store mapping of field type strings to Field class names
  - Support both programmatic registration and naming convention discovery
  - Validate that registered classes implement FieldInterface
- **Methods**: `registerField()`, `getFieldClass()`, `isRegistered()`

**ActionRegistry**
- **Purpose**: Manage available action types
- **Similar to FieldRegistry** but for Action classes

**FieldFactory**
- **Purpose**: Create Field instances
- **Responsibilities**:
  - Use FieldRegistry to find appropriate class for field type
  - Instantiate Field objects
  - Handle fallback to naming convention discovery for backward compatibility

**ActionFactory**
- **Purpose**: Create Action instances
- **Similar to FieldFactory** but for Action classes

#### Dependency System

**DependencyManager**
- **Purpose**: Evaluate field dependencies and manage conditional field visibility
- **Responsibilities**:
  - Evaluate dependency conditions against current form state
  - Determine which fields should be hidden/shown based on other field values
  - Generate dependency configuration for JavaScript client-side handling
  - Support complex dependency expressions (AND, OR, NOT)
- **Does NOT**: Parse dependency syntax (that's FormParser's job)

### Interaction Flow

#### Form Definition Parsing
1. **User provides** form definition text with `<form>...</form>` syntax (including new dependency syntax)
2. **FormParser** splits text into field lines, action lines, metadata
3. **For each field line**:
   - Parse dependency conditions from field definition (e.g., `if:other_field=value`)
   - FieldFactory creates appropriate Field instance
   - Field.parseConfiguration() converts field-specific arguments to structured config
   - Parser creates FieldData with field type, config, default values, and dependency conditions
4. **For each action line**: Similar process with ActionFactory and Action classes
5. **Parser returns** complete FormData object with all dependency information

#### Form Rendering
1. **FormRenderer** receives FormData
2. **DependencyManager** evaluates all dependency conditions against current form state
3. **Creates** modern dokuwiki\Form\Form object
4. **For each FieldData**:
   - Skip if DependencyManager determined field should be hidden
   - Gets corresponding Field instance from FieldFactory
   - Calls Field.buildFormElement(FieldData) to create form element
   - Adds element to form object with dependency data attributes for JavaScript
5. **DependencyManager** generates JavaScript configuration for client-side dependency handling
6. **Returns** HTML form with embedded dependency configuration

#### Form Submission Processing
1. **FormProcessor** receives POST data and original FormData
2. **DependencyManager** evaluates dependencies to determine which fields should be processed
3. **For each field** (excluding those hidden by dependencies):
   - Calls Field.validateInput() with user input
4. **Creates updated FormData** with validated values and error states
5. **If validation fails**: Re-render form with error messages (using DependencyManager for visibility)
6. **If validation succeeds**:
   - For each action: Call Action.execute(FormData)
   - Collect thanks messages
   - Display success page

#### Plugin Integration
1. **Other plugins** listen for PLUGIN_BUREAUCRACY_REGISTER_FIELDS/ACTIONS events
2. **During registration**: Plugins call FieldRegistry.registerField() with custom types
3. **During parsing**: FieldFactory checks registry first, falls back to naming conventions
4. **Custom Field/Action classes** implement FieldInterface/ActionInterface

### Key Architectural Benefits

**Separation of Concerns**:
- Data storage separate from behavior
- Parsing separate from rendering and processing
- Field logic separate from form orchestration

**Immutable Data Objects**:
- FieldData and FormData are immutable
- State changes create new objects
- Easier to reason about and test

**Clear Interfaces**:
- FieldInterface and ActionInterface define clear contracts
- Plugin integration through well-defined APIs
- Easy to add new field and action types

**Modern Form API**:
- Uses dokuwiki\Form throughout
- No deprecated Doku_Form dependencies
- Clean, modern form element creation

**Extensible Registration**:
- Plugin registration through events
- Backward compatibility with naming conventions
- Clear extension points for other plugins

