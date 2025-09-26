# OTTER Shorthand Reference for MCP Testing

## Overview
This reference provides the OTTER shorthand notation for efficient MCP testing communication and documentation.

## Core Shorthand

### OTRS
- **OTRS** = Otter Test and Regeneration Sequence

### Reports Page Elements
- **PR** = Preset Ranges
- **N** = None
- **PM** = Past Month  
- **A** = All
- **SD** = Start Date
- **ED** = End Date
- **ADR** = Active Date Range
- **EDR** = Edit Date Range

### Tables and Data
- **T** = Tables
- **S** = Systemwide
- **O** = Organizations
- **G** = Groups

### Columns
- **C** = Columns
- **R** = Registrations
- **E** = Enrollments
- **C** = Certificates

## Usage Examples

### Testing Commands
```
OTRS
1. delete all legacy cache files
2. regenerate for ccc, csu, and demo
3. test Demo reports page
A. ADR = All, THEN
B. ADR = PM
4. Return first row of data for all T
5. WAIT
```

### Documentation
- "Test PR functionality" = "Test Preset Ranges functionality"
- "Verify ADR display" = "Verify Active Date Range display"
- "Expand O T" = "Expand Organizations Tables"
- "Check R, E, C values" = "Check Registrations, Enrollments, Certificates values"

## Benefits
- **Efficiency**: Faster communication during testing sessions
- **Consistency**: Standardized terminology across all testing patterns
- **Clarity**: Reduces ambiguity in testing instructions
- **Documentation**: Cleaner, more concise testing documentation

## Integration with MCP Testing
This shorthand is integrated into all MCP testing patterns and can be used in:
- Testing session commands
- Documentation
- Bug reports
- Feature requests
- Automated testing scripts
