Create a complete Accounting Management module.

Requirements:

All Create and Edit operations must be performed inside Modal dialogs.
Delete should use confirmation modal.
View should also open in a modal.
Use DataTable for listing with Search, Filter and Pagination.

Modules:

1. Branch (ac_branches)
Fields:
- id
- name
- code
- location
- branch_head
- is_active
- timestamps

2. Payment Methods (ac_payment_methods)

Payment Method means the payment channel used to pay or receive money.

Examples:
- Cash
- Bank
- bKash
- Rocket
- Nagad
- Upay

Fields:
- id
- name
- is_active
- timestamps

3. Accounts (ac_accounts)

Represents cash/account holders.

Fields:
- id
- name
- employee_id
- designation
- user_id (foreign key from users table)
- branch_id (foreign key)
- opening_balance
- current_balance
- is_active
- timestamps

4. Master Particular (ac_master_particulars)

Fields:
- id
- name
- description
- type (Debit/Credit)
- is_active
- timestamps

5. Particular (ac_particulars)

Fields:
- id
- master_particular_id
- name
- code
- description
- is_active
- timestamps

6. Balance Receive (ac_balance_receives)

Fields:
- id
- receive_no (auto generated)
- receive_date
- branch_id
- account_id
- particular_id
- amount
- description
- attachment
- created_by
- timestamps

7. Expense (ac_expenses)

Fields:
- id
- expense_no (auto generated)
- expense_date
- payment_method_id
- branch_id
- account_id
- company_name
- receiver_name
- receiver_mobile
- total_amount
- description
- attachment
- created_by
- timestamps

Expense Items (ac_expense_details)

Fields:
- id
- expense_id
- particular_id
- invoice
- qty
- uom
- rate
- amount
- description

One Expense can have multiple Expense Details.

8. Expense IOU (ac_expense_ious)

Fields:
- id
- iou_no (auto generated)
- account_id
- employee_id
- payment_method_id
- branch_id
- issue_date
- adjust_date
- amount
- description
- receiver_name
- receiver_mobile
- status (Pending, Adjusted)
- timestamps

9. Transactions (ac_transactions)

Every financial operation should automatically create transaction records.

Fields:
- id
- transaction_date
- transaction_type
- reference_type
- reference_id
- branch_id
- account_id
- payment_method_id
- debit
- credit
- balance
- description
- created_by
- timestamps

Business Rules

- Every Expense automatically inserts Transaction.
- Every Balance Receive automatically inserts Transaction.
- Every IOU Issue automatically inserts Transaction.
- Every IOU Adjustment automatically inserts Transaction.
- Opening Balance should create an Opening transaction.
- Auto generate Voucher Numbers (EXP-000001, IOU-000001, BR-000001).
- Use foreign key constraints.
- Validate all required fields.
- Soft Delete where applicable.
- Use database transactions for save/update.
- Prevent deleting records that are already referenced.

UI Requirements

- Create using Modal.
- Edit using Modal.
- View using Modal.
- Delete Confirmation Modal.
- Responsive DataTable.
- Search.
- Filters.
- Bulk Delete.
- Export Excel & PDF.
- Active/Inactive Toggle.
- Proper Success/Error Notifications.
- dashboard with details
- use adminTheme
- use sidebar and permission


Code should follow Laravel best practices with clean architecture, relationships, validation, and reusable components.








>> branch jodi 1 ta hoy tahole auto selected thakbe 
>> accounts user jodi login kore tahole se shudhu tar tai dekhte pabe and tar account sob jaygay selected thakbe onno keo dhukle just accounst accounts er select disable dekhabe (for operational)
>> accounts user jodi login kore tahole se shudhu tar tai dekhte pabe and tar account sob jaygay selected thakbe onno keo dhukle accounts er select e sobgula dekhabe + all dekhte parbe dekhabe (for report)
>> accounts wise perticular select er option rakhbe checkbox jeno je accounst er je perticular select kora thakbe je jeno setukui dekhte pay, mane se login korle just targulai dekhbe , onno keo login korle sob dekhte parbe 

prottekta report jeno accounts, master perticular, perticular, date range, etc filter kore dekha jay 







