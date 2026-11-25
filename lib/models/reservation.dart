class Reservation {
  final String bookingId;
  final String customerName;
  final String phone;
  final int guests;
  final DateTime date;
  final String timeSlot;
  final String? specialRequests;
  final DateTime createdAt;

  Reservation({
    required this.bookingId,
    required this.customerName,
    required this.phone,
    required this.guests,
    required this.date,
    required this.timeSlot,
    this.specialRequests,
    required this.createdAt,
  });

  Map<String, dynamic> toJson() {
    return {
      'bookingId': bookingId,
      'customerName': customerName,
      'phone': phone,
      'guests': guests,
      'date': date.toIso8601String(),
      'timeSlot': timeSlot,
      'specialRequests': specialRequests,
      'createdAt': createdAt.toIso8601String(),
    };
  }

  factory Reservation.fromJson(Map<String, dynamic> json) {
    return Reservation(
      bookingId: json['bookingId'] as String,
      customerName: json['customerName'] as String,
      phone: json['phone'] as String,
      guests: (json['guests'] as num).toInt(),
      date: DateTime.parse(json['date'] as String),
      timeSlot: json['timeSlot'] as String,
      specialRequests: json['specialRequests'] as String?,
      createdAt: DateTime.parse(json['createdAt'] as String),
    );
  }
}
