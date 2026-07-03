# AI Feature Specification for Sistem Pengurusan Pembiayaan TEKUN Nasional (SPPT)

## Introduction

This document outlines five key Artificial Intelligence (AI) features
recommended for integration into the Sistem Pengurusan Pembiayaan TEKUN
Nasional (SPPT). These AI capabilities are designed to improve
operational efficiency, enhance risk management, and provide better
services to entrepreneurs applying for financing.

The AI components should function as **decision-support tools** for
officers rather than fully autonomous decision-makers. This approach
aligns with best practices in financial systems and public-sector
governance.

------------------------------------------------------------------------

# 1. AI Loan Risk Scoring

## Objective

To automatically evaluate the financial risk level of financing
applicants using machine learning models.

## Problem Addressed

Manual risk evaluation is time-consuming and inconsistent across
officers.

## AI Approach

Machine learning models analyze historical TEKUN financing data to
predict repayment probability.

## Data Inputs

-   Applicant demographic information
-   Business type
-   Business age
-   Monthly revenue
-   Existing financing obligations
-   Historical repayment behavior
-   Geographic business location

## Processing

The AI model calculates a **risk score** and assigns a risk category.

Example output:

Risk Score: 72/100\
Risk Category: Medium Risk\
Recommended Financing Limit: RM40,000

## Benefits

-   Faster loan evaluation
-   Reduced default risk
-   Consistent assessment standards

------------------------------------------------------------------------

# 2. AI Document OCR + Validation

## Objective

To automatically extract and validate data from uploaded applicant
documents.

## Problem Addressed

Manual verification of documents such as IC and bank statements is slow
and prone to human error.

## AI Approach

Use OCR (Optical Character Recognition) combined with AI-based document
understanding.

## Supported Documents

-   Identity Card (IC)
-   Business registration certificate
-   Bank statements
-   Business license
-   Supporting documents

## AI Processing Steps

1.  Detect document type
2.  Extract text data
3.  Identify key fields
4.  Validate against application form data

## Example Extraction

Document: Bank Statement

Extracted Data: Account Holder Name\
Account Number\
Average Monthly Balance

## Benefits

-   Faster document processing
-   Reduced manual workload
-   Higher data accuracy

------------------------------------------------------------------------

# 3. AI Fraud Detection

## Objective

To detect suspicious financing applications using pattern recognition.

## Problem Addressed

Fraudulent applications can result in financial losses and reputational
risk.

## AI Detection Methods

-   Duplicate identity detection
-   Bank account reuse analysis
-   Behavioral anomaly detection
-   Document similarity detection

## Example Alerts

Possible Fraud Indicators:

-   Same bank account used in multiple applications
-   Multiple applications from same device
-   Suspiciously identical documents

## Risk Scoring Output

Fraud Probability Score: 85%\
Status: Investigation Required

## Benefits

-   Early fraud detection
-   Protection of financing funds
-   Improved compliance and audit capability

------------------------------------------------------------------------

# 4. AI Repayment Prediction

## Objective

To predict the likelihood of late repayment or loan default.

## Problem Addressed

Loan repayment issues are typically detected only after payments are
missed.

## AI Model Inputs

-   Payment history
-   Business performance indicators
-   Industry category
-   Seasonal revenue patterns
-   Previous financing records

## Prediction Output

Probability of Late Payment: 63%

Recommended Action: - Send automated reminder - Schedule monitoring by
officer

## Benefits

-   Early intervention
-   Reduced non-performing loans (NPL)
-   Improved financial sustainability

------------------------------------------------------------------------

# 5. AI Chatbot for Applicants

## Objective

To assist applicants through an intelligent conversational interface.

## Problem Addressed

Applicants often require assistance understanding financing programs and
application procedures.

## Capabilities

The chatbot can: - Explain financing programs - Guide application
steps - Check application status - Answer frequently asked questions

## Example Queries

-   "Apakah syarat pembiayaan TEKUN?"
-   "Berapa maksimum pinjaman yang boleh dipohon?"
-   "Bagaimana semak status permohonan?"

## Integration

The chatbot can integrate with: - Public financing portal - Mobile
application - WhatsApp business integration

## Benefits

-   Reduced call center workload
-   Improved applicant experience
-   Faster information access

------------------------------------------------------------------------

# Conclusion

The integration of AI within SPPT will significantly enhance efficiency,
accuracy, and risk management. The recommended AI capabilities focus on:

1.  Risk evaluation
2.  Document automation
3.  Fraud prevention
4.  Repayment monitoring
5.  Applicant assistance

These features should be implemented as **AI-assisted decision support
tools** to enhance human decision-making rather than replacing it.
